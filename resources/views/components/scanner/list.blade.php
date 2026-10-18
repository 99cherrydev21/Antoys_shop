@props(['field' => 'items'])

<!-- Encabezado de la lista -->
<div class="mb-3 mt-5 flex items-center justify-between gap-3">
    <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Productos escaneados</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            <span x-text="items.length"></span>
            <span x-text="items.length === 1 ? 'producto' : 'productos'"></span> ·
            <span x-text="totalUnits"></span>
            <span x-text="totalUnits === 1 ? 'unidad' : 'unidades'"></span>
        </p>
    </div>

    <button type="button"
            @click="clearItems()"
            x-show="items.length > 0" x-cloak
            class="rounded-xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600
                   transition hover:bg-red-50
                   dark:border-red-800 dark:text-red-400 dark:hover:bg-gray-800
                   focus:outline-none focus:ring-2 focus:ring-red-400">
        Limpiar lista
    </button>
</div>

<!-- Buscador (aparece cuando la lista ya es larga) -->
<div x-show="items.length > 4 || filter" x-cloak class="relative mb-3">
    <input type="text"
           x-model="filter"
           @keydown.enter.prevent
           placeholder="Buscar por Id o nombre..."
           class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2 pr-10
                  text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500
                  dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">

    <button type="button"
            x-show="filter" x-cloak
            @click="filter = ''"
            class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400
                   transition-colors hover:text-gray-600 dark:hover:text-gray-200"
            title="Limpiar búsqueda">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

<!-- Estado vacío -->
<div x-show="items.length === 0" x-cloak
     class="rounded-2xl border border-dashed border-gray-300 bg-white px-5 py-10 text-center
            text-gray-500 shadow dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">
    <p class="font-medium text-gray-700 dark:text-gray-200">Aún no hay productos</p>
    <p class="mt-1 text-sm">Los productos que escanees aparecerán aquí.</p>
</div>

<!-- Sin resultados del filtro -->
<div x-show="items.length > 0 && filteredItems.length === 0" x-cloak
     class="rounded-2xl bg-white p-8 text-center text-gray-500 shadow
            dark:bg-gray-800 dark:text-gray-400">
    Ningún producto coincide con la búsqueda.
</div>

<!-- Tarjetas (si pasas contenido, reemplaza la tarjeta por defecto) -->
<div class="space-y-3">
    <template x-for="item in filteredItems" :key="item.id">
        @if (trim((string) $slot) === '')
            <x-scanner.card />
        @else
            {{ $slot }}
        @endif
    </template>
</div>

<!-- Campos ocultos: SIEMPRE de la lista completa, no de la filtrada -->
<template x-for="(item, index) in items" :key="'f' + item.id">
    <div class="hidden">
        <input type="hidden" :name="`{{ $field }}[${index}][product_id]`" :value="item.id">
        <input type="hidden" :name="`{{ $field }}[${index}][quantity]`" :value="item.quantity">
    </div>
</template>
