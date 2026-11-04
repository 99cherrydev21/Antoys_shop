@props(['placeholder' => 'Ej. 1 o Nombre del producto'])

{{--
    Al tocar una zona vacía de la pantalla (o al cargar la página) el escáner recupera el foco.
    Tocar botones, campos de texto o un popup NO lo roba, para que todo siga siendo editable.
--}}
<div
    class="relative"
    x-data
    x-init="$nextTick(() => $refs.scanner.focus({ preventScroll: true }))"
    @click.window="
        if ($event.target.closest('[role=dialog], input, textarea, select, a')) return;
        // Se espera un instante: si el clic abrió un popup, el escáner NO debe quitarle el foco
        setTimeout(() => {
            const popupOpen = [...document.querySelectorAll('[role=dialog]')]
                .some(d => getComputedStyle(d).display !== 'none');
            if (!popupOpen) $refs.scanner.focus({ preventScroll: true });
        }, 0);
    "
>
    {{-- Efecto luminoso en los bordes --}}
    <div
        class="pointer-events-none fixed inset-0 z-40 transition-opacity duration-300"
        :class="scanning ? 'opacity-100' : 'opacity-0'"
        aria-hidden="true"
    >
        <div class="absolute inset-0
            shadow-[inset_0_0_12px_2px_rgba(37,99,235,0.65)]
            sm:shadow-[inset_0_0_22px_5px_rgba(37,99,235,0.75)]">
        </div>

        <div class="absolute inset-0 border-[3px] border-blue-500/70
            animate-pulse
            shadow-[inset_0_0_25px_rgba(59,130,246,0.55)]">
        </div>
    </div>

    {{-- Input del escáner: diminuto e invisible, ya NO cubre la pantalla --}}
    <input
        x-ref="scanner"
        type="text"
        inputmode="none"
        autocomplete="off"
        autocapitalize="characters"
        spellcheck="false"
        tabindex="-1"
        aria-label="Zona de escaneo"
        @focus="scanning = true"
        @blur="scanning = false"
       @keydown.enter.prevent="scan($refs.scanner.value.replace(/'/g, '-')); $refs.scanner.value = ''"
        class="pointer-events-none fixed left-0 top-0 h-px w-px opacity-0"
    >

    {{-- Barra de búsqueda siempre visible --}}
    <div class="relative z-50 p-4">
        <div class="mx-auto max-w-3xl">
            <div class="relative">
                <input
                    x-ref="manualInput"
                    x-model="manualSku"
                    type="text"
                    autocomplete="off"
                    placeholder="{{ $placeholder }}"
                    @focus="manual = true"
                    @keydown.enter.prevent="addManual()"
                    class="w-full rounded-2xl border border-gray-200
                    bg-white/95 py-4 pl-12 pr-4 text-gray-800
                    shadow-lg backdrop-blur-xl
                    focus:border-blue-500 focus:outline-none
                    focus:ring-2 focus:ring-blue-400
                    dark:border-gray-700 dark:bg-gray-800/95
                    dark:text-white"
                >

                <svg
                    class="absolute left-4 top-1/2 h-5 w-5
                    -translate-y-1/2 text-blue-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
            </div>

            {{-- Resultados de búsqueda manual --}}
            <ul
                x-show="typeof manualResults !== 'undefined' && manualResults.length"
                x-cloak
                class="mt-2 overflow-hidden rounded-xl border
                border-gray-200 bg-white shadow-xl
                dark:border-gray-700 dark:bg-gray-800"
            >
                <template
                    x-for="p in (typeof manualResults !== 'undefined' ? manualResults : [])"
                    :key="p.id"
                >
                    <li>
                        <button
                            type="button"
                            @click="pickManual(p)"
                            class="flex w-full justify-between gap-3 px-4 py-3
                            text-left text-sm hover:bg-blue-50
                            dark:hover:bg-gray-700"
                        >
                            <span x-text="p.name"></span>
                            <span x-text="p.sku"></span>
                        </button>
                    </li>
                </template>
            </ul>

            {{-- Estado del escáner --}}
            <div class="mt-3 flex items-center justify-between">
                <span class="flex items-center gap-2 text-xs
                    text-gray-500 dark:text-gray-400">
                    <span
                        class="h-2 w-2 rounded-full"
                        :class="scanning ? 'bg-blue-500 animate-pulse' : 'bg-gray-400'"
                    ></span>
                    <span x-text="scanning
                        ? 'Escáner activo'
                        : 'Toca un espacio vacío para activar'"></span>
                </span>

                <button
                    type="button"
                    @click="manual = !manual"
                    class="text-xs font-semibold text-blue-600
                    hover:text-teal-500 dark:text-blue-400"
                >
                    Búsqueda manual
                </button>
            </div>

            {{-- Mensaje del escaneo --}}
            <div
                x-show="message"
                x-cloak
                x-transition
                class="mt-3 rounded-xl px-3 py-2 text-sm font-medium"
                :class="messageError
                    ? 'bg-red-50 text-red-700 dark:bg-red-900 dark:text-red-200'
                    : 'bg-green-50 text-green-700 dark:bg-green-900 dark:text-green-200'"
                x-text="message"
            ></div>
        </div>
    </div>
</div>
