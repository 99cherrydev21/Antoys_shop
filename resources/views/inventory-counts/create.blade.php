<x-app-layout>
    <!-- Contenedor con fondo adaptable y tus destellos -->
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300 relative">
        <x-background-glow />

        <div class="relative z-10 max-w-2xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            <!-- Encabezado con el botón de retroceso cuadrado/redondeado al lado -->
            <div class="mb-6 flex items-center gap-4">
                <a href="{{ route('inventory-counts.index') }}"
                   class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border border-gray-200/80 dark:border-gray-700/80 text-gray-700 dark:text-gray-200 shadow-md transition-all duration-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-105"
                   title="Volver al historial">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>

                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Nuevo inventario físico
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Registra la sesión antes de comenzar a contar la mercancía.
                    </p>
                </div>
            </div>

            <!-- Tarjeta contenedora principal Glassmorphism -->
            <div class="bg-white/80 dark:bg-gray-800/90 backdrop-blur-xl shadow-2xl rounded-3xl p-6 sm:p-10 border border-white/20 dark:border-gray-700/50">

                <!-- Manejo de Errores -->
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 p-4 text-sm text-red-600 dark:text-red-400 shadow-sm">
                        <p class="font-semibold mb-1">Por favor corrige los siguientes errores:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Formulario -->
                <form method="POST"
                      action="{{ route('inventory-counts.store') }}"
                      class="space-y-6">
                    @csrf

                   <!-- Fecha del inventario -->
                    <div>
                        <label for="count_date"
                            class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Fecha del inventario
                        </label>

                        <!-- Input visual de solo lectura (no permite modificar y SÍ se envía en el request) -->
                        <input id="count_date"
                            name="count_date"
                            type="date"
                            readonly
                            value="{{ old('count_date', now()->toDateString()) }}"
                            class="w-full rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900/80 px-4 py-3 text-gray-700 dark:text-gray-300 opacity-75 cursor-not-allowed focus:outline-none transition shadow-sm">

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            La fecha se establece automáticamente con el día de hoy.
                        </p>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label for="description"
                               class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Descripción <span class="text-xs font-normal text-gray-400">(Opcional)</span>
                        </label>

                        <textarea id="description"
                                  name="description"
                                  rows="3"
                                  maxlength="255"
                                  placeholder="Ej. Inventario general de la tienda..."
                                  class="w-full rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900/80 px-4 py-3 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-sm resize-none">{{ old('description') }}</textarea>
                    </div>

                    <!-- Aviso informativo -->


                    <!-- Botón de acción principal con gradiente Esmeralda/Teal -->
                    <div class="pt-2">
                        <button type="submit"
                                class="group relative inline-flex w-full items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-3.5 text-sm font-semibold text-white shadow-xl shadow-emerald-600/25 transition-all duration-300 hover:from-emerald-500 hover:to-teal-500 hover:shadow-emerald-600/40 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-300 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Crear inventario y preparar conteo</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
