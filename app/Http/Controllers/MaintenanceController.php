<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceLog;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use RuntimeException;
use Throwable;
use App\Services\SystemResetService;

class MaintenanceController extends Controller
{
    /**
     * Panel principal de mantenimiento.
     */
public function index()
{
    $user = auth()->user();

    $requests = MaintenanceRequest::with([
        'requester:id,name,phone,role_id',
        'technician:id,name,phone,role_id',
        'approver:id,name,phone,role_id',
        'executor:id,name,phone,role_id',
    ])
        ->latest()
        ->paginate(10);

    $notifications = $user->notifications()
        ->latest()
        ->take(5)
        ->get();

    return view('maintenance.index', compact(
        'requests',
        'user',
        'notifications'
    ));
}
    /**
     * Historial de mantenimiento.
     */
    public function history()
    {
        $logs = MaintenanceLog::with([
            'user:id,name,phone',
            'maintenanceRequest:id,type,status',
        ])
            ->latest()
            ->paginate(15);

        return view('maintenance.history', compact('logs'));
    }

    /**
     * Generar respaldo para una solicitud.
     * Solo puede hacerlo el técnico asignado.
     */
    public function generateBackup(
        MaintenanceRequest $maintenanceRequest,
        DatabaseBackupService $backupService
    ) {
        $this->authorizeTechnician($maintenanceRequest);

        abort_unless(
            $maintenanceRequest->status === MaintenanceRequest::STATUS_PENDING_BACKUP,
            409,
            'La solicitud no está esperando un respaldo.'
        );

        abort_if(
            $maintenanceRequest->backup_filename,
            409,
            'Esta solicitud ya tiene un respaldo generado.'
        );

        try {
            $backup = $backupService->create();

            DB::transaction(function () use ($maintenanceRequest, $backup) {
                $request = MaintenanceRequest::whereKey($maintenanceRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $request->status !== MaintenanceRequest::STATUS_PENDING_BACKUP ||
                    $request->backup_filename
                ) {
                    throw new RuntimeException(
                        'La solicitud cambió mientras se generaba el respaldo.'
                    );
                }

                $request->update([
                    'backup_filename' => $backup['filename'],
                    'backup_checksum' => $backup['checksum'],
                    'backup_size' => $backup['size'],
                ]);

                MaintenanceLog::create([
                    'maintenance_request_id' => $request->id,
                    'user_id' => auth()->id(),
                    'action' => 'backup_generated',
                    'description' => 'El técnico generó el respaldo de la base de datos.',
                    'metadata' => [
                        'filename' => $backup['filename'],
                        'checksum' => $backup['checksum'],
                        'size' => $backup['size'],
                    ],
                ]);
            });

            return back()->with(
                'success',
                'El respaldo se generó correctamente. Ahora puedes descargarlo.'
            );

        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'No se pudo generar el respaldo: '.$exception->getMessage()
            );
        }
    }

    /**
     * Descargar respaldo privado.
     */
    public function downloadBackup(
        MaintenanceRequest $maintenanceRequest
    ): BinaryFileResponse {
        $this->authorizeTechnician($maintenanceRequest);

        abort_unless(
            $maintenanceRequest->backup_filename,
            404,
            'Todavía no existe un respaldo para esta solicitud.'
        );

        $filename = basename($maintenanceRequest->backup_filename);

        $path = storage_path(
            'app/private/maintenance-backups/'.$filename
        );

        abort_unless(
            File::exists($path) && File::isFile($path),
            404,
            'El archivo de respaldo ya no está disponible.'
        );

        $checksum = hash_file('sha256', $path);

        abort_unless(
            hash_equals(
                (string) $maintenanceRequest->backup_checksum,
                $checksum
            ),
            409,
            'El respaldo no coincide con el hash registrado. No lo descargues ni continúes.'
        );

        MaintenanceLog::create([
            'maintenance_request_id' => $maintenanceRequest->id,
            'user_id' => auth()->id(),
            'action' => 'backup_downloaded',
            'description' => 'El técnico descargó el respaldo.',
            'metadata' => [
                'filename' => $filename,
            ],
        ]);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    /**
     * Confirmar que el técnico guardó el respaldo.
     */
    public function confirmBackup(
        MaintenanceRequest $maintenanceRequest
    ) {
        $this->authorizeTechnician($maintenanceRequest);

        abort_unless(
            $maintenanceRequest->status === MaintenanceRequest::STATUS_PENDING_BACKUP,
            409,
            'La solicitud ya no está esperando la confirmación del respaldo.'
        );

        abort_unless(
            $maintenanceRequest->backup_filename &&
            $maintenanceRequest->backup_checksum,
            409,
            'Primero debes generar el respaldo.'
        );

        $filename = basename($maintenanceRequest->backup_filename);

        $path = storage_path(
            'app/private/maintenance-backups/'.$filename
        );

        abort_unless(
            File::exists($path) && File::isFile($path),
            404,
            'No se encontró el archivo de respaldo.'
        );

        $currentChecksum = hash_file('sha256', $path);

        abort_unless(
            hash_equals(
                (string) $maintenanceRequest->backup_checksum,
                $currentChecksum
            ),
            409,
            'El respaldo cambió y no puede confirmarse.'
        );

        DB::transaction(function () use ($maintenanceRequest, $currentChecksum) {
            $request = MaintenanceRequest::whereKey($maintenanceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== MaintenanceRequest::STATUS_PENDING_BACKUP) {
                abort(409, 'La solicitud ya fue procesada.');
            }

            $request->update([
                'status' => MaintenanceRequest::STATUS_BACKUP_CONFIRMED,
                'backup_confirmed_at' => now(),
                'backup_checksum' => $currentChecksum,
            ]);

            MaintenanceLog::create([
                'maintenance_request_id' => $request->id,
                'user_id' => auth()->id(),
                'action' => 'backup_confirmed',
                'description' => 'El técnico confirmó que descargó y guardó el respaldo.',
                'metadata' => [
                    'filename' => $request->backup_filename,
                    'checksum' => $currentChecksum,
                ],
            ]);
        });

        return back()->with(
            'success',
            'Respaldo confirmado. La solicitud ya puede pasar a autorización del jefe.'
        );
    }

    /**
     * Verificar que el usuario sea el técnico asignado.
     */
    private function authorizeTechnician(
        MaintenanceRequest $maintenanceRequest
    ): void {
        abort_unless(
            auth()->user()->isTecnico(),
            403,
            'Esta operación está reservada para el técnico.'
        );

        abort_unless(
            (int) $maintenanceRequest->technician_id === (int) auth()->id(),
            403,
            'No estás asignado a esta solicitud de mantenimiento.'
        );
    }

/**
 * Crear solicitud de restablecimiento.
 * Solo el jefe puede solicitarlo.
 */
public function store(Request $request)
{
    abort_unless(
        auth()->user()->isJefe(),
        403,
        'Solo el jefe puede solicitar un restablecimiento.'
    );

    $validated = $request->validate([
        'description' => ['required', 'string', 'max:2000'],
    ]);

    $technician = \App\Models\User::query()
        ->whereHas('role', function ($query) {
            $query->whereRaw('LOWER(name) = ?', ['tecnico']);
        })
        ->where('is_active', true)
        ->sole();

    $maintenanceRequest = MaintenanceRequest::create([
        'requested_by' => auth()->id(),
        'technician_id' => $technician->id,
        'type' => 'system_reset',
        'status' => MaintenanceRequest::STATUS_PENDING_BACKUP,
        'description' => $validated['description'],
    ]);

    MaintenanceLog::create([
        'maintenance_request_id' => $maintenanceRequest->id,
        'user_id' => auth()->id(),
        'action' => 'reset_requested',
        'description' => 'El jefe solicitó un restablecimiento del sistema.',
        'metadata' => [
            'technician_id' => $technician->id,
        ],
    ]);

    return redirect()
        ->route('maintenance.index')
        ->with('success', 'Solicitud creada y asignada al técnico.');
}

/**
 * Autorizar restablecimiento.
 * Solo el jefe puede autorizarlo.
 */
public function approve(MaintenanceRequest $maintenanceRequest)
{
    abort_unless(
        auth()->user()->isJefe(),
        403,
        'Solo el jefe puede autorizar el restablecimiento.'
    );

    abort_unless(
        $maintenanceRequest->status === MaintenanceRequest::STATUS_BACKUP_CONFIRMED &&
        $maintenanceRequest->backup_confirmed_at &&
        $maintenanceRequest->backup_checksum,
        409,
        'No se puede autorizar: falta confirmar un respaldo válido.'
    );

    DB::transaction(function () use ($maintenanceRequest) {
        $request = MaintenanceRequest::whereKey($maintenanceRequest->id)
            ->lockForUpdate()
            ->firstOrFail();

        if (
            $request->status !== MaintenanceRequest::STATUS_BACKUP_CONFIRMED ||
            !$request->backup_confirmed_at ||
            !$request->backup_checksum
        ) {
            abort(409, 'La solicitud ya no está disponible para autorización.');
        }

        $request->update([
            'status' => MaintenanceRequest::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        MaintenanceLog::create([
            'maintenance_request_id' => $request->id,
            'user_id' => auth()->id(),
            'action' => 'reset_approved',
            'description' => 'El jefe autorizó el restablecimiento del sistema.',
        ]);
    });

    return back()->with(
        'success',
        'Restablecimiento autorizado. El técnico ya puede ejecutarlo.'
    );
}

/**
 * Ejecutar el restablecimiento autorizado.
 * Solo el técnico asignado puede ejecutarlo.
 */
public function execute(
    MaintenanceRequest $maintenanceRequest,
    SystemResetService $resetService
) {
    $this->authorizeTechnician($maintenanceRequest);

    abort_unless(
        $maintenanceRequest->status === MaintenanceRequest::STATUS_APPROVED,
        409,
        'La solicitud todavía no está aprobada.'
    );

    try {
        $resetService->execute(
            $maintenanceRequest,
            auth()->user()
        );

        return redirect()
            ->route('maintenance.index')
            ->with('success', 'El restablecimiento se completó correctamente.');

    } catch (\Throwable $exception) {
        report($exception);

        return redirect()
            ->route('maintenance.index')
            ->with('error', 'No se pudo completar el restablecimiento. Revisa los registros.');
    }
}
}
