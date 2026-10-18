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
    </x-scanner.page>
</x-app-layout>
