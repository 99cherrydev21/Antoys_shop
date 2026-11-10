
<x-app-layout>
    <div class="min-h-screen bg-gray-100 py-8 transition-colors duration-300 dark:bg-gray-950">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Encabezado --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">
                        Historial de restablecimiento
                    </h1>
                </div>

                <a href="{{ route('maintenance.index') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 19l-7-7 7-7"/>
                    </svg>

                </a>
            </div>

            {{-- Resumen --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-white">
                            Auditoría de operaciones
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $logs->total() }} registros encontrados.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">

                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <h2 class="font-bold text-gray-900 dark:text-white">
                        Actividad registrada
                    </h2>
                </div>

                @forelse ($logs as $log)
                    @php
                        $actionLabels = [
                            'backup_generated' => 'Respaldo generado',
                            'backup_downloaded' => 'Respaldo descargado',
                            'backup_confirmed' => 'Respaldo confirmado',
                            'reset_requested' => 'Restablecimiento solicitado',
                            'reset_approved' => 'Restablecimiento autorizado',
                            'reset_started' => 'Restablecimiento iniciado',
                            'reset_completed' => 'Restablecimiento completado',
                            'reset_failed' => 'Restablecimiento fallido',
                        ];

                        $actionClasses = [
                            'backup_generated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                            'backup_downloaded' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                            'backup_confirmed' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300',
                            'reset_requested' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                            'reset_approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                            'reset_started' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                            'reset_completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                            'reset_failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                        ];
                    @endphp

                    <div class="border-b border-gray-100 p-5 last:border-b-0 dark:border-gray-800">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                            <div class="flex min-w-0 gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 3c-2.755 0-5.293.927-7.322 2.484A11.95 11.95 0 003 12c0 4.97 3.03 9.23 7.322 11 4.292-1.77 7.322-6.03 7.322-11 0-1.657-.337-3.236-.944-4.684z"/>
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-gray-900 dark:text-white">
                                            {{ $actionLabels[$log->action] ?? ucfirst(str_replace('_', ' ', $log->action)) }}
                                        </h3>

                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $actionClasses[$log->action] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                            {{ $log->action }}
                                        </span>
                                    </div>

                                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">
                                        {{ $log->description ?? 'Sin descripción.' }}
                                    </p>

                                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                        <span>
                                            Usuario:
                                            <strong class="text-gray-700 dark:text-gray-300">
                                                {{ $log->user?->name ?? 'Usuario eliminado' }}
                                            </strong>
                                        </span>

                                        <span>
                                            Solicitud:
                                            <strong class="text-gray-700 dark:text-gray-300">
                                                {{ $log->maintenance_request_id ? '#'.$log->maintenance_request_id : 'Sin solicitud' }}
                                            </strong>
                                        </span>
                                    </div>

                                    @if (is_array($log->metadata) && count($log->metadata))
                                        <details class="mt-3">
                                            <summary class="cursor-pointer text-xs font-semibold text-teal-700 hover:text-teal-600 dark:text-teal-400">
                                                Ver detalles técnicos
                                            </summary>

                                            <div class="mt-2 rounded-lg bg-gray-50 p-3 dark:bg-gray-950">
                                                @foreach ($log->metadata as $key => $value)
                                                    <p class="break-all py-1 text-xs text-gray-600 dark:text-gray-400">
                                                        <span class="font-semibold text-gray-800 dark:text-gray-200">
                                                            {{ $key }}:
                                                        </span>

                                                        {{ is_scalar($value) || $value === null ? ($value ?? '—') : json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                                    </p>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </div>

                            <div class="shrink-0 text-left sm:text-right">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                    {{ $log->created_at?->format('d/m/Y') }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $log->created_at?->format('H:i:s') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-14 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>

                        <h3 class="mt-4 font-semibold text-gray-900 dark:text-white">
                            No hay registros todavía
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Las operaciones de mantenimiento aparecerán aquí.
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- Paginación --}}
            @if ($logs->hasPages())
                <div>
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
