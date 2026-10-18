<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lg dark:border-gray-700 dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Tipo de movimiento</h2>

    <div class="grid grid-cols-2 gap-3">
        <label class="cursor-pointer rounded-xl border-2 p-3 text-center transition focus-within:ring-2 focus-within:ring-indigo-500"
               :class="type === 'entrada'
                   ? 'border-green-500 bg-green-50 text-green-800 dark:bg-green-900 dark:text-green-100'
                   : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700'">
            <input type="radio" name="type" value="entrada" x-model="type" class="sr-only">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0l-6-6m6 6l6-6"/>
            </svg>
            <span class="mt-1 block font-semibold">Entrada</span>
            <span class="block text-[11px] opacity-80">Suma al stock</span>
        </label>

        <label class="cursor-pointer rounded-xl border-2 p-3 text-center transition focus-within:ring-2 focus-within:ring-indigo-500"
               :class="type === 'salida'
                   ? 'border-red-500 bg-red-50 text-red-800 dark:bg-red-900 dark:text-red-100'
                   : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700'">
            <input type="radio" name="type" value="salida" x-model="type" class="sr-only">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-6 6m6-6l6 6"/>
            </svg>
            <span class="mt-1 block font-semibold">Salida</span>
            <span class="block text-[11px] opacity-80">Resta del stock</span>
        </label>
    </div>
</div>
