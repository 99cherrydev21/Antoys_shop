@php($total = max(1, $inventoryCount->items->count()))

<x-app-layout>
        <!-- Contenedor con fondo adaptable y tus destellos -->
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300 relative">
        <x-background-glow />
    <x-scanner.page
        title="Contar inventario"
        :subtitle="'Folio: ' . $inventoryCount->folio"
        :back="route('inventory-counts.show', $inventoryCount)"
        :action="route('inventory-counts.count.store', $inventoryCount)"
        :lookup="route('inventory-counts.lookup', $inventoryCount)"
        :limit-types="[]"
        :min-quantity="0"
        :show-stock="false"
        :initial-items="$initialItems"
        :labels="['default' => 'Guardar conteo']"
        :reason="false"
        placeholder="ANT-0000001">

        <x-slot name="aside">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lg
                        dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Resumen</h2>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Fecha</dt>
                        <dd class="font-semibold text-gray-800 dark:text-white">
                            {{ $inventoryCount->count_date->format('d/m/Y') }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Responsable</dt>
                        <dd class="font-semibold text-gray-800 dark:text-white">
                            {{ $inventoryCount->user->name }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-5">
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Productos escaneados</span>
                        <span class="font-semibold text-gray-800 dark:text-white">
                            <span x-text="items.length"></span> / {{ $inventoryCount->items->count() }}
                        </span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-full rounded-full bg-indigo-600 transition-all"
                             :style="{ width: Math.min(100, items.length / {{ $total }} * 100) + '%' }"></div>
                    </div>
                </div>
            </div>
        </x-slot>

      <!-- Campo oculto para que los no escaneados cuenten como cero automáticamente -->
<input type="hidden" name="missing_as_zero" value="1">

<!-- Confirmación antes de guardar -->
<div x-data="{
        open: false,
        confirmed: false,
        form: null,
        confirmSave() {
            this.confirmed = true;
            this.open = false;
            this.form.requestSubmit();
        }
    }"
    x-init="
        form = $el.closest('form');
        form.addEventListener('submit', (e) => {
            if (!confirmed) {
                e.preventDefault();
                e.stopImmediatePropagation();
                open = true;
            }
        }, true);
    "
    @keydown.escape.window="open = false">

 <template x-teleport="body">
                    <div x-show="open" x-cloak
                         class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm p-4"
                         x-transition.opacity>

                        <div @click.outside="open = false"
                             class="w-full max-w-2xl rounded-3xl bg-white dark:bg-gray-800 shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]">

                            <!-- Encabezado del Modal -->
                            <div class="bg-red-50 dark:bg-red-950/50 p-6 border-b border-red-100 dark:border-red-900/50 flex items-start gap-4 shrink-0">
                                <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-red-800 dark:text-red-200">¿Finalizar inventario?</h3>
                                    <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                                        Una vez guardado, <strong>no podrás modificar los números</strong>. Revisa las diferencias calculadas antes de continuar.
                                    </p>
                                </div>
                            </div>
                            <!-- Botones del Modal -->
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex flex-col sm:flex-row justify-end gap-3 shrink-0">
                                <button type="button" @click="open = false"
                                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    Revisar de nuevo
                                </button>
                                <button type="button" @click="confirmSave()"
                                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-teal-500 hover:from-blue-600 hover:to-teal-600 transition-all duration-300 shadow-lg shadow-teal-500/30 hover:shadow-xl hover:shadow-teal-500/40 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Sí, cerrar inventario
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
</div>
    </x-scanner.page>
</x-app-layout>
