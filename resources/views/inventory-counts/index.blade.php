<x-app-layout>
    <!-- Contenedor con fondo adaptable y tus destellos -->
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300 relative"
         x-data="{ newCountId: {{ json_encode(session('new_count_id')) }} }"
         x-init="
            if (newCountId) {
                setTimeout(() => {
                    const el = document.getElementById('count-' + newCountId);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 300);
            }
         ">
        <x-background-glow />

        <div class="relative z-10 max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            <!-- Encabezado con el botón de retroceso tipo tarjeta al lado -->
            <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <!-- Botón de retorno cuadrado/redondeado exacto al estilo de la imagen -->
                    <a href="{{ route('products.index') }}"
                       class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border border-gray-200/80 dark:border-gray-700/80 text-gray-700 dark:text-gray-200 shadow-md transition-all duration-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-105"
                       title="Volver al catálogo">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>

                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                            Inventario físico
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Historial de conteos y diferencias de existencias.
                        </p>
                    </div>
                </div>

                <a href="{{ route('inventory-counts.create') }}"
                   class="group relative inline-flex items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-xl shadow-emerald-600/25 transition-all duration-300 hover:from-emerald-500 hover:to-teal-500 hover:shadow-emerald-600/40 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-300 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Nuevo inventario</span>
                </a>
            </div>

            <!-- Mensaje de éxito -->
            @if (session('success'))
                <div class="mb-6 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 p-4 text-sm font-medium shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Tarjeta contenedora principal Glassmorphism -->
            <div class="bg-white/80 dark:bg-gray-800/90 backdrop-blur-xl shadow-2xl rounded-3xl border border-white/20 dark:border-gray-700/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50/80 dark:bg-gray-900/60 text-gray-500 dark:text-gray-400 uppercase text-xs font-semibold tracking-wider border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-4">Folio</th>
                                <th class="px-6 py-4">Fecha</th>
                                <th class="px-6 py-4">Descripción</th>
                                <th class="px-6 py-4">Responsable</th>
                                <th class="px-6 py-4">Productos</th>
                                <th class="px-6 py-4">Estado</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200/60 dark:divide-gray-700/60">
                            @forelse ($counts as $count)
                                <tr id="count-{{ $count->id }}"
                                    class="transition-colors duration-300 hover:bg-gray-50/50 dark:hover:bg-gray-700/30"
                                    :class="{ 'bg-indigo-50/80 dark:bg-indigo-950/50': newCountId === {{ $count->id }} }">

                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2.5">
                                            <a href="{{ route('inventory-counts.show', $count) }}"
                                               class="font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition">
                                                {{ $count->folio }}
                                            </a>

                                            <!-- Etiqueta condicional con Alpine si es el recién creado -->
                                            <template x-if="newCountId === {{ $count->id }}">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 animate-pulse border border-indigo-200 dark:border-indigo-800">
                                                    ¡Nuevo!
                                                </span>
                                            </template>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300 font-medium">
                                        {{ $count->count_date->format('d/m/Y') }}
                                    </td>

                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                        {{ $count->description ?: 'Sin descripción' }}
                                    </td>

                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300 font-medium">
                                        {{ $count->user->name ?? 'Usuario eliminado' }}
                                    </td>

                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300 font-medium">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-gray-100 dark:bg-gray-900 text-xs font-bold text-gray-700 dark:text-gray-300">
                                            {{ $count->items_count }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $count->status === 'en_proceso' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' }}">
                                            {{ str_replace('_', ' ', ucfirst($count->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        Todavía no hay inventarios físicos registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="p-6 border-t border-gray-200/60 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/20">
                    {{ $counts->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
