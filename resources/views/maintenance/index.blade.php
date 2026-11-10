
<x-app-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-950 py-8 transition-colors duration-300">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- ENCABEZADO --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>


                    <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">
Restablecimiento del sistema                     </h1>

                </div>

                <a href="{{ route('maintenance.history') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Historial
                </a>
            </div>

            {{-- NOTIFICACIONES DEL JEFE --}}
            @if ($user->isJefe() && $notifications->isNotEmpty())
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="font-bold text-gray-900 dark:text-white">
                        Notificaciones de mantenimiento
                    </h2>

                    <div class="mt-3 space-y-3">
                        @foreach ($notifications as $notification)
                            <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-950">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ ($notification->data['result'] ?? '') === 'completed'
                                        ? 'Restablecimiento completado'
                                        : 'Restablecimiento fallido' }}
                                </p>

                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $notification->data['message'] ?? 'Sin detalles disponibles.' }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $notification->created_at?->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- SOLICITAR RESTABLECIMIENTO: JEFE --}}
            @if ($user->isJefe())
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        Solicitar restablecimiento
                    </h2>

                    <form method="POST"
                          action="{{ route('maintenance.store') }}"
                          class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <label for="description"
                                   class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Motivo del restablecimiento
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                maxlength="2000"
                                required
                                placeholder="Describe por qué se necesita restablecer el sistema..."
                                class="w-full rounded-xl border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >{{ old('description') }}</textarea>

                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            onclick="return confirm('¿Deseas crear una solicitud de restablecimiento?');"
                            class="rounded-xl bg-teal-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-700"
                        >
                            Crear solicitud
                        </button>
                    </form>
                </div>
            @endif

            {{-- MENSAJES --}}
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                    <p class="font-semibold">Se encontraron errores:</p>

                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- SOLICITUDES --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        Solicitudes de mantenimiento
                    </h2>

                    <span class="rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        {{ $requests->total() }} solicitudes
                    </span>
                </div>

                @forelse ($requests as $maintenanceRequest)

                    @php
                        $status = $maintenanceRequest->status;

                        $statusLabels = [
                            'pending_backup' => 'Pendiente de respaldo',
                            'backup_confirmed' => 'Respaldo confirmado',
                            'approved' => 'Autorizado',
                            'running' => 'En ejecución',
                            'completed' => 'Completado',
                            'failed' => 'Error',
                            'cancelled' => 'Cancelado',
                        ];

                        $statusClasses = [
                            'pending_backup' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                            'backup_confirmed' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300',
                            'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                            'running' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                            'cancelled' => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                        ];

                        $isAssignedTechnician =
                            $user->isTecnico() &&
                            (int) $maintenanceRequest->technician_id === (int) $user->id;

                        $canGenerateBackup =
                            $isAssignedTechnician &&
                            $status === 'pending_backup' &&
                            !$maintenanceRequest->backup_filename;

                        $canDownloadBackup =
                            $isAssignedTechnician &&
                            $status === 'pending_backup' &&
                            !empty($maintenanceRequest->backup_filename);
                    @endphp

                    <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">

                        {{-- CABECERA --}}
                        <div class="flex flex-col gap-3 border-b border-gray-100 p-5 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-bold text-gray-900 dark:text-white">
                                        Solicitud #{{ $maintenanceRequest->id }}
                                    </h3>

                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$status] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $statusLabels[$status] ?? ucfirst($status) }}
                                    </span>
                                </div>

                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Creada:
                                    {{ $maintenanceRequest->created_at?->format('d/m/Y H:i') ?? 'No disponible' }}
                                </p>
                            </div>

                            <span class="inline-flex w-fit items-center rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                {{ $maintenanceRequest->type === 'system_reset'
                                    ? 'Restablecimiento del sistema'
                                    : ucfirst(str_replace('_', ' ', $maintenanceRequest->type)) }}
                            </span>
                        </div>

                        <div class="grid gap-5 p-5 md:grid-cols-2">

                            {{-- INFORMACIÓN DE LA SOLICITUD --}}
                            <div class="space-y-3">

                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        Solicitante
                                    </p>

                                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $maintenanceRequest->requester?->name ?? 'Usuario no disponible' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        Técnico asignado
                                    </p>

                                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $maintenanceRequest->technician?->name ?? 'Sin asignar' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        Autorizado por
                                    </p>

                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $maintenanceRequest->approver?->name ?? 'Pendiente' }}
                                    </p>
                                </div>

                                @if ($maintenanceRequest->description)
                                    <div>
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Descripción
                                        </p>

                                        <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
                                            {{ $maintenanceRequest->description }}
                                        </p>
                                    </div>
                                @endif

                            </div>

                            {{-- RESPALDO Y ACCIONES --}}
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950/50">

                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                    Respaldo de seguridad
                                </h4>

                                @if ($maintenanceRequest->backup_filename)

                                    <div class="mt-3 space-y-2 text-sm">

                                        <p class="break-all font-medium text-gray-800 dark:text-gray-200">
                                            {{ $maintenanceRequest->backup_filename }}
                                        </p>

                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Tamaño:
                                            {{ number_format(($maintenanceRequest->backup_size ?? 0) / 1024, 2) }} KB
                                        </p>

                                        <p class="break-all text-xs text-gray-500 dark:text-gray-400">
                                            SHA-256:
                                            {{ $maintenanceRequest->backup_checksum }}
                                        </p>

                                        @if ($maintenanceRequest->backup_confirmed_at)
                                            <p class="text-xs font-semibold text-teal-700 dark:text-teal-400">
                                                Confirmado:
                                                {{ $maintenanceRequest->backup_confirmed_at->format('d/m/Y H:i') }}
                                            </p>
                                        @endif

                                    </div>

                                @else

                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Todavía no se ha generado un respaldo.
                                    </p>

                                @endif

                                {{-- ACCIONES EXCLUSIVAS DEL TÉCNICO --}}
                                @if ($isAssignedTechnician)

                                    {{-- GENERAR RESPALDO --}}
                                    @if ($canGenerateBackup)

                                        <form method="POST"
                                              action="{{ route('maintenance.backup.generate', $maintenanceRequest) }}"
                                              class="mt-4"
                                              onsubmit="return confirm('¿Deseas generar el respaldo completo de la base de datos?');">

                                            @csrf

                                            <button type="submit"
                                                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">

                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M7 10l5 5m0 0l5-5m-5 5V3"/>
                                                </svg>

                                                Generar respaldo SQL
                                            </button>
                                        </form>

                                    @endif

                                    {{-- DESCARGAR Y CONFIRMAR RESPALDO --}}
                                    @if ($canDownloadBackup)

                                        <a href="{{ route('maintenance.backup.download', $maintenanceRequest) }}"
                                           class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-teal-600 bg-white px-4 py-3 text-sm font-semibold text-teal-700 transition hover:bg-teal-50 dark:border-teal-500 dark:bg-gray-900 dark:text-teal-300 dark:hover:bg-teal-950/40">

                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M12 10v6m0 0l-3-3m3 3l3-3m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v8a2 2 0 01-2 2z"/>
                                            </svg>

                                            Descargar respaldo
                                        </a>

                                        <form method="POST"
                                              action="{{ route('maintenance.backup.confirm', $maintenanceRequest) }}"
                                              class="mt-3"
                                              onsubmit="return confirm('Confirma que descargaste y guardaste el respaldo SQL en un lugar seguro. ¿Continuar?');">

                                            @csrf

                                            <button type="submit"
                                                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900">

                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M5 13l4 4L19 7"/>
                                                </svg>

                                                Confirmar respaldo guardado
                                            </button>
                                        </form>

                                    @endif

                                @elseif ($user->isTecnico() && $status === 'pending_backup')

                                    <p class="mt-4 rounded-lg bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                                        Esta solicitud está asignada a otro técnico.
                                    </p>

                                @endif

                                {{-- AUTORIZACIÓN EXCLUSIVA DEL JEFE --}}
                                @if ($user->isJefe() && $status === 'backup_confirmed')

                                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/10">

                                        <div class="flex items-start gap-3">

                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z"/>
                                                </svg>
                                            </div>

                                            <div>
                                                <h4 class="font-semibold text-amber-900 dark:text-amber-200">
                                                    Autorización del jefe
                                                </h4>

                                                <p class="mt-1 text-sm text-amber-800 dark:text-amber-300">
                                                    El técnico confirmó el respaldo.
                                                    Puedes autorizar el restablecimiento del sistema.
                                                </p>
                                            </div>

                                        </div>

                                        <form
                                            method="POST"
                                            action="{{ route('maintenance.approve', $maintenanceRequest) }}"
                                            class="mt-4"
                                            onsubmit="return confirm('¿Confirmas que autorizas el restablecimiento del sistema? Esta acción permitirá que el técnico pueda ejecutarlo.');"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M5 13l4 4L19 7"/>
                                                </svg>

                                                Autorizar restablecimiento
                                            </button>
                                        </form>

                                    </div>

                                @elseif ($status === 'backup_confirmed')

                                    <p class="mt-4 rounded-lg bg-teal-50 p-3 text-sm font-medium text-teal-800 dark:bg-teal-900/20 dark:text-teal-300">
                                        El respaldo fue confirmado. Está pendiente la autorización del jefe.
                                    </p>

                                @endif

                                {{-- ESTADOS POSTERIORES --}}
                                @if ($status === 'approved')

                                    <p class="mt-4 rounded-lg bg-blue-50 p-3 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                        Solicitud autorizada. Pendiente de ejecución por el técnico.
                                    </p>

                                    {{-- EJECUCIÓN EXCLUSIVA DEL TÉCNICO ASIGNADO --}}
                                    @if ($isAssignedTechnician)

                                        <form
                                            method="POST"
                                            action="{{ route('maintenance.execute', $maintenanceRequest) }}"
                                            class="mt-3"
                                            onsubmit="return confirm('ATENCIÓN: Se eliminarán los registros operativos definidos en el mantenimiento y todas las existencias quedarán en cero. ¿Deseas continuar?');"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z"/>
                                                </svg>

                                                Ejecutar restablecimiento
                                            </button>
                                        </form>

                                    @endif

                                @elseif ($status === 'running')

                                    <p class="mt-4 rounded-lg bg-indigo-50 p-3 text-sm text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-300">
                                        El mantenimiento está en ejecución.
                                    </p>

                                @elseif ($status === 'completed')

                                    <p class="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-300">
                                        El mantenimiento terminó correctamente.
                                    </p>

                                @elseif ($status === 'failed')

                                    <p class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-300">
                                        El mantenimiento terminó con errores.
                                    </p>

                                    @if ($maintenanceRequest->error_message)
                                        <p class="mt-2 break-words text-xs text-red-700 dark:text-red-300">
                                            {{ $maintenanceRequest->error_message }}
                                        </p>
                                    @endif

                                @elseif ($status === 'cancelled')

                                    <p class="mt-4 rounded-lg bg-gray-100 p-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        Esta solicitud fue cancelada.
                                    </p>

                                @endif

                            </div>
                        </div>
                    </article>

                @empty

                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center dark:border-gray-700 dark:bg-gray-900">

                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>

                        <h3 class="mt-4 font-semibold text-gray-900 dark:text-white">
                            No hay solicitudes de mantenimiento
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Cuando se registre una solicitud, aparecerá aquí.
                        </p>

                    </div>

                @endforelse

                {{-- PAGINACIÓN --}}
                @if ($requests->hasPages())
                    <div class="pt-2">
                        {{ $requests->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
