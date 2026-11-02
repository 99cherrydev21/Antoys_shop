<x-app-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300 relative">
        <x-background-glow />

        <div class="relative z-10 max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8"
             x-data="{ showToast: false, submitting: false }"
             @keydown.escape.window="showToast = false">

            @php
                $total      = $inventoryCount->items->count();
                $counted    = $inventoryCount->items->whereNotNull('quantity_counted')->count();
                $pending    = $total - $counted;
                $enProceso  = $inventoryCount->status === 'en_proceso';
            @endphp

            <div class="bg-white/80 dark:bg-gray-800/90 backdrop-blur-xl shadow-2xl rounded-3xl p-6 sm:p-8 border border-white/20 dark:border-gray-700/50">

                <!-- Encabezado -->
                <div class="mb-6 border-b border-gray-200/80 dark:border-gray-700/80 pb-5 flex items-center gap-4">
                    <a href="{{ route('inventory-counts.index') }}"
                       class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border border-gray-200/80 dark:border-gray-700/80 text-gray-700 dark:text-gray-200 shadow-md transition-all duration-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-105"
                       title="Volver al historial">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Resultado del conteo</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Folio: {{ $inventoryCount->folio }}</p>
                    </div>
                </div>

                <!-- Errores -->
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

                <!-- Información general -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 bg-gray-50/50 dark:bg-gray-900/40 p-4 rounded-2xl border border-gray-200/60 dark:border-gray-700/60">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</p>
                        <p class="font-semibold text-gray-800 dark:text-white mt-0.5">{{ $inventoryCount->count_date->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Responsable</p>
                        <p class="font-semibold text-gray-800 dark:text-white mt-0.5">{{ $inventoryCount->user->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</p>
                        <p class="font-semibold text-gray-800 dark:text-white mt-0.5">
                            {{ ucfirst(str_replace('_', ' ', $inventoryCount->status)) }}
                        </p>
                    </div>
                </div>

                @if ($inventoryCount->description)
                    <p class="mb-6 text-sm text-gray-600 dark:text-gray-300 italic">"{{ $inventoryCount->description }}"</p>
                @endif

                <form method="POST"
                      action="{{ route('inventory-counts.items.save-all', $inventoryCount) }}"
                      id="inventory-count-form"
                      @submit="submitting = true">
                    @csrf

                    <!-- Tabla -->
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
                                    @php
                                        $isCounted = $item->quantity_counted !== null;
                                        $physical  = $isCounted ? $item->quantity_counted : 0;
                                        $system    = $item->stock_expected;
                                        $calculatedDiff = $physical - $system;
                                    @endphp
                                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition">
                                        <td class="px-4 py-4">
                                            <div class="font-semibold text-gray-800 dark:text-white">{{ $item->product->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $item->product->sku }}</div>
                                        </td>

                                        <td class="px-4 py-4 text-center font-medium text-gray-700 dark:text-gray-300">
                                            {{ $system }}
                                        </td>

                                        <!-- Físico: valor visible y campo oculto (vacío si todavía no se cuenta) -->
                                        <td class="px-4 py-4 text-center">
                                            <span class="font-bold text-gray-800 dark:text-gray-100">
                                                {{ $isCounted ? $item->quantity_counted : '—' }}
                                            </span>
                                            <!-- Si no se contó, se manda vacío y el servidor lo deja sin contar (no lo convierte en 0) -->
                                            <input type="hidden" name="items[{{ $item->id }}]" value="{{ $item->quantity_counted }}">
                                        </td>

                                        <!-- Diferencia -->
                                        <td class="px-4 py-4 text-center text-sm">
                                            @if (! $isCounted)
                                                <span class="text-gray-400">—</span>
                                            @elseif ($calculatedDiff > 0)
                                                <span class="font-bold text-emerald-600 dark:text-emerald-400">+{{ $calculatedDiff }}</span>
                                            @elseif ($calculatedDiff < 0)
                                                <span class="font-bold text-red-600 dark:text-red-400">{{ $calculatedDiff }}</span>
                                            @else
                                                <span class="font-bold text-gray-600 dark:text-gray-300">0</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-4 text-center text-xs">
                                            @if ($isCounted)
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 px-2.5 py-1 font-medium">
                                                    Contado
                                                </span>
                                            @elseif ($enProceso)
                                                <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 px-2.5 py-1 font-medium"
                                                      title="Conservará su stock actual">
                                                    Sin contar
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 px-2.5 py-1 font-medium">
                                                    Sin contar
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

                    <!-- Botón de acción -->
                    @if ($enProceso && $inventoryCount->items->isNotEmpty())
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Revisa las cantidades antes de guardar. Los productos sin contar conservarán su stock actual.
                            </p>

                            <div>
                                <button type="submit"
                                        @disabled($counted === 0)
                                        @click="showToast = true; setTimeout(() => showToast = false, 3000)"
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-semibold transition shadow-lg shadow-indigo-600/30 disabled:cursor-not-allowed disabled:opacity-50">
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
