@props(['placeholder' => 'Ej. 1 o Nombre del producto'])

<div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lg
            dark:border-gray-700 dark:bg-gray-800 sm:p-5">

    <!-- Zona de escaneo con tonos azules claros y verdes -->
    <div class="relative overflow-hidden rounded-2xl border-2 border-dashed transition"
         :class="[
            items.length ? 'px-4 py-4' : 'px-5 py-8',
            scanning
                ? 'border-teal-500 bg-teal-50 dark:bg-slate-900 dark:border-teal-500'
                : 'border-blue-200 bg-blue-50/50 hover:border-teal-400 dark:border-gray-600 dark:bg-slate-900 dark:hover:border-teal-500'
         ]">

        <!-- Input invisible que cubre toda la zona -->
        <input x-ref="scanner"
               type="text"
               inputmode="none"
               autocomplete="off"
               autocapitalize="characters"
               spellcheck="false"
               aria-label="Zona de escaneo"
               @focus="scanning = true"
               @blur="scanning = false"
               @keydown.enter.prevent="scan($refs.scanner.value)"
               class="absolute inset-0 h-full w-full cursor-pointer opacity-0">

        <div class="pointer-events-none flex select-none items-center gap-4"
             :class="items.length ? 'flex-row text-left' : 'flex-col text-center'">

            <span class="relative inline-flex shrink-0 items-center justify-center rounded-full transition"
                  :class="[
                    items.length ? 'h-12 w-12' : 'h-16 w-16',
                    scanning
                        ? 'bg-gradient-to-tr from-blue-500 to-teal-500 text-white shadow-lg shadow-teal-500/30'
                        : 'bg-blue-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400'
                  ]">
                <span x-show="scanning" x-cloak
                      class="absolute inline-flex h-full w-full animate-ping rounded-full bg-teal-400 opacity-60"></span>
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="relative"
                     :class="items.length ? 'h-6 w-6' : 'h-8 w-8'"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 20h1"/>
                </svg>
            </span>

            <div>
                <p class="text-base font-semibold text-gray-800 dark:text-white"
                   x-text="scanning ? 'Listo para escanear' : 'Toca aquí para escanear'"></p>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                   x-text="scanning
                        ? 'Acerca el lector al código Data Matrix'
                        : 'Toca esta zona y luego escanea el código'"></p>
            </div>
        </div>
    </div>

    <!-- Mensaje del escaneo -->
    <div x-show="message" x-cloak x-transition
         class="mt-3 rounded-xl px-3 py-2 text-sm font-medium"
         :class="messageError
            ? 'bg-red-50 text-red-700 dark:bg-red-900 dark:text-red-200'
            : 'bg-green-50 text-green-700 dark:bg-green-900 dark:text-green-200'"
         x-text="message">
    </div>

    <!-- Captura manual -->
    <button type="button"
            @click="manual = !manual; if (manual) $nextTick(() => $refs.manualInput.focus())"
            class="mt-3 text-sm font-medium text-blue-600 hover:text-teal-600 dark:text-blue-400 dark:hover:text-teal-400 hover:underline transition-colors"
            x-text="manual ? 'Ocultar búsqueda manual' : 'Buscar manualmente (Número o Nombre)'">
    </button>

    <div x-show="manual" x-cloak class="mt-3 flex gap-2">
        <input x-ref="manualInput"
               x-model="manualSku"
               type="text"
               autocomplete="off"
               placeholder="{{ $placeholder }}"
               @keydown.enter.prevent="addManual()"
               class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2 text-gray-800
                      focus:outline-none focus:ring-2 focus:ring-teal-500
                      dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 transition-shadow">
        <button type="button"
                @click="addManual()"
                class="shrink-0 rounded-xl bg-gradient-to-r from-blue-500 to-teal-500 px-4 py-2 text-sm font-semibold text-white
                       transition-all duration-300 hover:from-blue-600 hover:to-teal-600 hover:shadow-lg hover:shadow-teal-500/30
                       focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            Buscar
        </button>
    </div>
</div>
