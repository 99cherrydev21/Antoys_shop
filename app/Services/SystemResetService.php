<?php

namespace App\Services;

use App\Models\MaintenanceLog;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class SystemResetService
{
    /**
     * Ejecuta un restablecimiento previamente autorizado.
     *
     * IMPORTANTE:
     * - Solo puede ejecutarlo el técnico asignado.
     * - Requiere una solicitud aprobada por un jefe.
     * - Verifica la integridad del respaldo antes de borrar información.
     * - Usa una transacción para las operaciones de base de datos.
     * - Conserva los datos maestros.
     */
    public function execute(
        MaintenanceRequest $maintenanceRequest,
        User $technician
    ): void {
        $connection = DB::connection();
        $lockName = 'antoys_system_reset';

        // Comprobar el rol y el estado del técnico.
        if (!$technician->isTecnico() || !$technician->is_active) {
            throw new RuntimeException(
                'El usuario no es un técnico activo autorizado.'
            );
        }

        // Bloqueo global para impedir dos restablecimientos simultáneos.
        $lock = $connection->selectOne(
            'SELECT GET_LOCK(?, 0) AS acquired',
            [$lockName]
        );

        if ((int) ($lock->acquired ?? 0) !== 1) {
            throw new RuntimeException(
                'Ya existe otro restablecimiento en ejecución.'
            );
        }

        $receiptPaths = [];

        try {
            /*
             * La transacción y el bloqueo de fila se realizan antes
             * de validar el archivo para evitar utilizar datos obsoletos
             * de la solicitud.
             */
            $connection->transaction(function () use (
                $connection,
                $maintenanceRequest,
                $technician,
                &$receiptPaths
            ) {
                $request = MaintenanceRequest::whereKey(
                    $maintenanceRequest->id
                )
                    ->lockForUpdate()
                    ->firstOrFail();

                // La solicitud debe estar aprobada por un jefe.
                if (
                    $request->status !== MaintenanceRequest::STATUS_APPROVED
                ) {
                    throw new RuntimeException(
                        'La solicitud no está autorizada o ya fue ejecutada.'
                    );
                }

                // Verificar que el técnico sea el asignado.
                if (
                    (int) $request->technician_id !== (int) $technician->id
                ) {
                    throw new RuntimeException(
                        'El técnico no está asignado a esta solicitud.'
                    );
                }

                // Comprobar que existe la confirmación del respaldo.
                if (
                    !$request->backup_confirmed_at ||
                    !$request->backup_checksum ||
                    !$request->backup_filename
                ) {
                    throw new RuntimeException(
                        'La solicitud no cuenta con un respaldo confirmado.'
                    );
                }

                // Validar el nombre del archivo.
                $filename = (string) $request->backup_filename;

                if (
                    $filename === '' ||
                    basename($filename) !== $filename
                ) {
                    throw new RuntimeException(
                        'El nombre del respaldo no es válido.'
                    );
                }

                // Comprobar que el respaldo existe.
                $backupPath = storage_path(
                    'app/private/maintenance-backups/'.$filename
                );

                if (
                    !File::exists($backupPath) ||
                    !File::isFile($backupPath)
                ) {
                    throw new RuntimeException(
                        'No se encontró el respaldo confirmado.'
                    );
                }

                // Comprobar que el archivo tiene contenido.
                $backupSize = File::size($backupPath);

                if ($backupSize <= 0) {
                    throw new RuntimeException(
                        'El archivo de respaldo está vacío.'
                    );
                }

                // Verificar el tamaño registrado, cuando esté disponible.
                if (
                    $request->backup_size !== null &&
                    (int) $request->backup_size !== (int) $backupSize
                ) {
                    throw new RuntimeException(
                        'El tamaño del respaldo no coincide con el registrado.'
                    );
                }

                // Verificar la integridad SHA-256.
                $actualChecksum = hash_file('sha256', $backupPath);

                if (
                    !$actualChecksum ||
                    !hash_equals(
                        (string) $request->backup_checksum,
                        $actualChecksum
                    )
                ) {
                    throw new RuntimeException(
                        'El respaldo no coincide con su SHA-256 registrado.'
                    );
                }

                /*
                 * Registrar el inicio dentro de la misma transacción.
                 * Si algo falla después, este cambio también se revierte.
                 */
                $request->update([
                    'status' => MaintenanceRequest::STATUS_RUNNING,
                    'executed_by' => $technician->id,
                    'started_at' => now(),
                    'error_message' => null,
                ]);

                MaintenanceLog::create([
                    'maintenance_request_id' => $request->id,
                    'user_id' => $technician->id,
                    'action' => 'reset_started',
                    'description' => 'El técnico inició el restablecimiento del sistema.',
                    'metadata' => [
                        'backup_filename' => $filename,
                        'backup_checksum' => $actualChecksum,
                        'backup_size' => $backupSize,
                    ],
                ]);

                /*
                 * Guardar las rutas de los tickets antes de eliminar ventas.
                 * Solo se eliminarán archivos dentro de receipts/.
                 */
                $receiptPaths = $connection->table('sales')
                    ->whereNotNull('receipt_path')
                    ->pluck('receipt_path')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                // 1. Eliminar detalles de conteos físicos.
                $connection->table('inventory_count_items')->delete();

                // 2. Eliminar conteos físicos.
                $connection->table('inventory_counts')->delete();

                // 3. Eliminar detalles de ventas.
                $connection->table('sale_items')->delete();

                // 4. Eliminar ventas.
                $connection->table('sales')->delete();

                // 5. Eliminar movimientos de inventario.
                $connection->table('inventory_movements')->delete();

                // 6. Poner todas las existencias en cero.
                $connection->table('products')->update([
                    'stock' => 0,
                ]);

                /*
                 * No se eliminan productos, imágenes, usuarios, roles,
                 * configuraciones, contadores SKU ni registros
                 * de mantenimiento.
                 */

                // 7. Marcar la solicitud como completada.
                $request->update([
                    'status' => MaintenanceRequest::STATUS_COMPLETED,
                    'finished_at' => now(),
                    'error_message' => null,
                ]);

                // 8. Registrar el resultado.
                MaintenanceLog::create([
                    'maintenance_request_id' => $request->id,
                    'user_id' => $technician->id,
                    'action' => 'reset_completed',
                    'description' => 'El restablecimiento terminó correctamente. Las existencias quedaron en cero.',
                    'metadata' => [
                        'sales_receipts_found' => count($receiptPaths),
                        'stock_reset' => true,
                        'backup_verified' => true,
                    ],
                ]);
            });

            /*
             * La transacción ya fue confirmada.
             * Ahora se eliminan exclusivamente los tickets.
             */
            $this->deleteReceipts(
                $receiptPaths,
                $maintenanceRequest,
                $technician
            );

            // Notificar por separado: un error de notificación
            // no debe convertir un restablecimiento exitoso en fallido.
            $this->notifyRequesterSafely(
                $maintenanceRequest,
                'completed',
                'El restablecimiento del sistema terminó correctamente.'
            );

        } catch (Throwable $exception) {
            report($exception);

            /*
             * Si la transacción falló, normalmente la solicitud
             * volverá a estar en estado approved.
             * Solo en ese caso la marcamos como failed.
             */
            try {
                $connection->transaction(function () use (
                    $maintenanceRequest,
                    $technician,
                    $exception
                ) {
                    $request = MaintenanceRequest::whereKey(
                        $maintenanceRequest->id
                    )
                        ->lockForUpdate()
                        ->first();

                    if (
                        $request &&
                        $request->status === MaintenanceRequest::STATUS_APPROVED
                    ) {
                        $request->update([
                            'status' => MaintenanceRequest::STATUS_FAILED,
                            'executed_by' => $technician->id,
                            'finished_at' => now(),
                            'error_message' => mb_substr(
                                $exception->getMessage(),
                                0,
                                2000
                            ),
                        ]);

                        MaintenanceLog::create([
                            'maintenance_request_id' => $request->id,
                            'user_id' => $technician->id,
                            'action' => 'reset_failed',
                            'description' => 'El restablecimiento no pudo completarse.',
                            'metadata' => [
                                'error' => mb_substr(
                                    $exception->getMessage(),
                                    0,
                                    1000
                                ),
                            ],
                        ]);
                    }
                });
            } catch (Throwable $loggingException) {
                // No ocultar el error original si también falla el registro.
                report($loggingException);
            }

            $this->notifyRequesterSafely(
                $maintenanceRequest,
                'failed',
                'El restablecimiento no pudo completarse. Revisa el historial de mantenimiento.'
            );

            throw $exception;

        } finally {
            // Liberar el bloqueo global.
            try {
                $connection->selectOne(
                    'SELECT RELEASE_LOCK(?) AS released',
                    [$lockName]
                );
            } catch (Throwable $lockException) {
                report($lockException);
            }
        }
    }

    /**
     * Eliminar exclusivamente archivos dentro de receipts/.
     */
    private function deleteReceipts(
        array $paths,
        MaintenanceRequest $maintenanceRequest,
        User $technician
    ): void {
        foreach ($paths as $receiptPath) {
            $normalized = str_replace(
                '\\',
                '/',
                trim((string) $receiptPath)
            );

            // Rechazar rutas externas a receipts/ o con traversal.
            if (
                !str_starts_with($normalized, 'receipts/') ||
                str_contains($normalized, '..') ||
                str_contains($normalized, "\0")
            ) {
                continue;
            }

            $fullPath = storage_path(
                'app/public/'.$normalized
            );

            try {
                // Si el archivo ya no existe, no hay nada que eliminar.
                if (!File::exists($fullPath)) {
                    continue;
                }

                if (!File::isFile($fullPath)) {
                    throw new RuntimeException(
                        'La ruta del ticket no corresponde a un archivo.'
                    );
                }

                if (!File::delete($fullPath)) {
                    throw new RuntimeException(
                        'No se pudo eliminar el ticket: '.$normalized
                    );
                }

            } catch (Throwable $exception) {
                report($exception);

                try {
                    MaintenanceLog::create([
                        'maintenance_request_id' => $maintenanceRequest->id,
                        'user_id' => $technician->id,
                        'action' => 'receipt_cleanup_failed',
                        'description' => 'No se pudo eliminar un archivo de ticket después del restablecimiento.',
                        'metadata' => [
                            'path' => $normalized,
                            'error' => mb_substr(
                                $exception->getMessage(),
                                0,
                                1000
                            ),
                        ],
                    ]);
                } catch (Throwable $loggingException) {
                    report($loggingException);
                }
            }
        }
    }

    /**
     * Enviar notificación sin alterar el resultado del restablecimiento.
     */
    private function notifyRequesterSafely(
        MaintenanceRequest $maintenanceRequest,
        string $result,
        string $message
    ): void {
        try {
            $requester = User::find(
                $maintenanceRequest->requested_by
            );

            if (!$requester) {
                return;
            }

            $requester->notify(
                new \App\Notifications\MaintenanceResultNotification(
                    $maintenanceRequest->id,
                    $result,
                    $message
                )
            );
        } catch (Throwable $exception) {
            // La notificación fallida no modifica el estado del mantenimiento.
            report($exception);
        }
    }
}
