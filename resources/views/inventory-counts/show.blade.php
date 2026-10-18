<x-app-layout>
    <!-- Contenedor con fondo adaptable y tus destellos -->
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300 relative">
        <x-background-glow />

        <div class="relative z-10 max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8" x-data="{ showToast: false }">

            <!-- Tarjeta contenedora principal -->
            <div class="bg-white/80 dark:bg-gray-800/90 backdrop-blur-xl shadow-2xl rounded-3xl p-6 sm:p-8 border border-white/20 dark:border-gray-700/50">

                <!-- Encabezado -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 border-b border-gray-200 dark:border-gray-700 pb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                            Inventario físico
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Folio: {{ $inventoryCount->folio }}
                        </p>
                    </div>

                    <a href="{{ route('inventory-counts.index') }}"
                       class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        Volver al historial
                    </a>
                </div>

                <!-- Mensajes de Error -->
                @if ($errors->any())
                    <div class="mb-6 bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 p-4 rounded-2xl text-sm">
                        <p class="font-semibold mb-1">No se pudo completar la operación:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Información general del inventario -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 bg-gray-50/50 dark:bg-gray-900/40 p-4 rounded-2xl border border-gray-200/60 dark:border-gray-700/60">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</p>
                        <p class="font-semibold text-gray-800 dark:text-white mt-0.5">
                            {{ $inventoryCount->count_date->format('d/m/Y') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Responsable</p>
                        <p class="font-semibold text-gray-800 dark:text-white mt-0.5">
                            {{ $inventoryCount->user->name }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</p>
                        <p class="font-semibold text-gray-800 dark:text-white mt-0.5">
                            {{ ucfirst(str_replace('_', ' ', $inventoryCount->status)) }}
                        </p>
                    </div>
                </div>

                @if ($inventoryCount->description)
                    <p class="mb-6 text-sm text-gray-600 dark:text-gray-300 italic">
                        "{{ $inventoryCount->description }}"
                    </p>
                @endif

                <!-- Formulario de conteo -->
                <form method="POST"
                      action="{{ route('inventory-counts.items.save-all', $inventoryCount) }}"
                      id="inventory-count-form">
                    @csrf

                    <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700 mb-6">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/60">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Producto</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Sistema</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Físico</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Diferencia</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white/50 dark:bg-gray-800/30">
                                @forelse ($inventoryCount->items as $item)
                                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition">
                                        <td class="px-4 py-4">
                                            <div class="font-semibold text-gray-800 dark:text-white">
                                                {{ $item->product->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                                {{ $item->product->sku }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-4 text-center font-medium text-gray-700 dark:text-gray-300">
                                            {{ $item->stock_expected }}
                                        </td>

                                        <td class="px-4 py-4 text-center">
                                            @if ($inventoryCount->status === 'en_proceso')
                                                <input
                                                    type="number"
                                                    name="items[{{ $item->id }}]"
                                                    min="0"
                                                    max="2147483647"
                                                    step="1"
                                                    required
                                                    value="{{ old('items.' . $item->id, $item->quantity_counted) }}"
                                                    placeholder="0"
                                                    class="w-28 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-center font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                            @else
                                                <span class="font-semibold text-gray-700 dark:text-gray-200">
                                                    {{ $item->quantity_counted ?? 'Sin contar' }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-4 text-center text-sm">
                                            @if ($item->quantity_counted === null)
                                                <span class="text-gray-400">Pendiente</span>
                                            @elseif ($item->difference < 0)
                                                <span class="font-bold text-red-600 dark:text-red-400">{{ $item->difference }}</span>
                                            @elseif ($item->difference > 0)
                                                <span class="font-bold text-emerald-600 dark:text-emerald-400">+{{ $item->difference }}</span>
                                            @else
                                                <span class="font-bold text-gray-600 dark:text-gray-300">0</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-4 text-center text-xs">
                                            @if ($item->counted_at)
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 px-2.5 py-1 font-medium">
                                                    Contado
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 px-2.5 py-1 font-medium">
                                                    Pendiente
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No hay productos incluidos en este inventario.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Estadísticas resumidas -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <div class="rounded-2xl bg-white/60 dark:bg-gray-900/50 p-4 border border-gray-200/60 dark:border-gray-700/60">
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total de productos</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-white mt-1">
                                {{ $inventoryCount->items->count() }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/20 p-4 border border-emerald-200/40 dark:border-emerald-800/40">
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Cantidades guardadas</p>
                            <p class="text-xl font-bold text-emerald-700 dark:text-emerald-400 mt-1">
                                {{ $inventoryCount->items->whereNotNull('quantity_counted')->count() }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-amber-50/60 dark:bg-amber-950/20 p-4 border border-amber-200/40 dark:border-amber-800/40">
                            <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">Pendientes</p>
                            <p class="text-xl font-bold text-amber-700 dark:text-amber-400 mt-1">
                                {{ $inventoryCount->items->whereNull('quantity_counted')->count() }}
                            </p>
                        </div>
                    </div>

                    <!-- Botón de acción y Modal de Éxito tipo Popup Central -->
                    @if ($inventoryCount->status === 'en_proceso' && $inventoryCount->items->isNotEmpty())
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Revisa las cantidades antes de guardar. Los faltantes se completarán automáticamente con cero.
                            </p>

                            <!-- Campo oculto para missing_as_zero automático -->
                            <input type="hidden" name="missing_as_zero" value="1">

                            <div>
                                <button type="submit"
                                        @click="showToast = true; setTimeout(() => showToast = false, 3000)"
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-semibold transition shadow-lg shadow-indigo-600/30">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Guardar conteo
                                </button>
                            </div>
                        </div>
                    @endif
                </form>

            </div>

            <!-- Popup / Modal Central Llamativo Animado -->
            <div x-show="showToast"
                 x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-90"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-90"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-md p-4">

                <div class="relative w-full max-w-sm rounded-3xl bg-white p-6 text-center shadow-2xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <!-- Icono animado de éxito -->
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        ¡Guardado exitoso!
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Se han guardado tus cantidades correctamente. Redirigiendo...
                    </p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
