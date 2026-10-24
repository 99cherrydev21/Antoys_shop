<x-app-layout>

<!-- Contenedor principal: limita el ancho en escritorio y lo centra -->
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <!-- Encabezado: responsivo con mejor separación -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <!-- Título -->
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            Catálogo de productos
        </h1>

        <!-- Botones de acciones -->
        <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:gap-3">

            <!-- Botón: Entradas / Salidas -->
            <a href="{{ route('inventory.operations.create') }}"
               class="group relative inline-flex items-center justify-center gap-2.5
                      rounded-xl sm:rounded-2xl
                      bg-white/80 dark:bg-gray-800/80 backdrop-blur-md
                      border border-gray-200/80 dark:border-gray-700/80
                      px-3 py-2.5 sm:px-5 sm:py-3
                      text-xs sm:text-sm font-semibold
                      text-gray-700 dark:text-gray-200 shadow-md
                      transition-all duration-300
                      hover:bg-violet-50 dark:hover:bg-violet-950/30
                      hover:border-violet-300 dark:hover:border-violet-700
                      hover:text-violet-600 dark:hover:text-violet-400
                      hover:shadow-lg hover:-translate-y-0.5
                      focus:outline-none focus:ring-2 focus:ring-violet-500
                      focus:ring-offset-2 dark:focus:ring-offset-gray-900">

                <svg xmlns="http://www.w3.org/2000/svg"
                     class="h-4 w-4 sm:h-5 sm:w-5 shrink-0 text-purple-500 transition-transform duration-300 group-hover:scale-110"
                     fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>

                <span class="truncate">
                    <span class="hidden sm:inline">Entradas / Salidas</span>
                    <span class="sm:hidden">Movs.</span>
                </span>
            </a>

            <!-- Botón: Inventario físico -->
            <a href="{{ route('inventory-counts.index') }}"
               class="group relative inline-flex items-center justify-center gap-2.5
                      rounded-xl sm:rounded-2xl
                      bg-white/80 dark:bg-gray-800/80 backdrop-blur-md
                      border border-gray-200/80 dark:border-gray-700/80
                      px-3 py-2.5 sm:px-5 sm:py-3
                      text-xs sm:text-sm font-semibold
                      text-gray-700 dark:text-gray-200 shadow-md
                      transition-all duration-300
                      hover:bg-violet-50 dark:hover:bg-violet-950/30
                      hover:border-violet-300 dark:hover:border-violet-700
                      hover:text-violet-600 dark:hover:text-violet-400
                      hover:shadow-lg hover:-translate-y-0.5
                      focus:outline-none focus:ring-2 focus:ring-violet-500
                      focus:ring-offset-2 dark:focus:ring-offset-gray-900">

                <svg xmlns="http://www.w3.org/2000/svg"
                     class="h-4 w-4 sm:h-5 sm:w-5 shrink-0 text-violet-500 transition-transform duration-300 group-hover:scale-110"
                     fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>

                <span class="truncate">
                    <span class="hidden sm:inline">Inventario físico</span>
                    <span class="sm:hidden">Inv.</span>
                </span>
            </a>

        </div>
    </div>

    <!-- Buscador: con ancho máximo para que no se estire al infinito -->
    <form method="GET" action="{{ route('products.index') }}" class="mb-6 flex gap-2 items-center">
        <div class="relative w-full max-w-lg" x-data="{ query: '{{ request('search') }}' }">
            <input
                type="text"
                name="search"
                x-model="query"
                @input.debounce.300ms="$el.form.submit()"
                placeholder="Buscar por Id o nombre..."
                class="w-full bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >

            <template x-if="query">
                <a
                    href="{{ route('products.index') }}"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 transition-colors"
                    title="Limpiar búsqueda"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </template>
        </div>
    </form>

    <!-- Listado de productos -->
    <div class="space-y-3">

        @forelse ($products as $product)

            @php
                $gallery = $product->images->map(fn ($img) => [
                    'src' => asset('storage/' . $img->path),
                    'alt' => $img->alt_text ?? $product->name,
                ])->values();

                $primaryImage = $product->images->firstWhere('is_primary', true)
                    ?? $product->images->first();

                $primaryIndex = $primaryImage
                    ? ($product->images->search(fn ($i) => $i->id === $primaryImage->id) ?: 0)
                    : 0;

                $thumb = $primaryImage
                    ? asset('storage/' . ($primaryImage->thumb_path ?? $primaryImage->path))
                    : null;
            @endphp

            <div
                x-data="{
                    W: 96,            // ancho de cada botón (editar / eliminar)
                    startX: 0,
                    startY: 0,
                    startOffset: 0,
                    offset: 0,        // + = derecha (editar), - = izquierda (eliminar)
                    tracking: false,
                    moved: false,

                    start(e) {
                        if (e.pointerType === 'mouse' && e.button !== 0) return;
                        this.startX = e.clientX;
                        this.startY = e.clientY;
                        this.startOffset = this.offset;
                        this.tracking = true;
                        this.moved = false;
                    },

                    move(e) {
                        if (!this.tracking) return;
                        const dx = e.clientX - this.startX;
                        const dy = e.clientY - this.startY;

                        if (Math.abs(dx) > 10 || Math.abs(dy) > 10) this.moved = true;

                        if (Math.abs(dx) > Math.abs(dy)) {
                            this.offset = Math.max(-this.W, Math.min(this.W, this.startOffset + dx));
                        }
                    },

                    snap() {
                        if (this.offset < -this.W / 2) return -this.W;
                        if (this.offset > this.W / 2) return this.W;
                        return 0;
                    },

                    end() {
                        if (!this.tracking) return;
                        this.offset = this.snap();
                        this.tracking = false;
                        setTimeout(() => this.moved = false, 0);
                    },

                    cancel() {
                        this.tracking = false;
                        this.offset = this.startOffset < 0 ? -this.W : (this.startOffset > 0 ? this.W : 0);
                    },

                    openViewer(index = 0) {
                        if (this.moved) return;
                        $dispatch('open-product-image', { images: @js($gallery), index })
                    }
                }"
                class="relative overflow-hidden rounded-2xl shadow-lg
                       border border-gray-200 dark:border-gray-700
                       lg:max-w-5xl lg:mx-auto"
            >

                <!-- EDITAR (detrás, a la izquierda; se ve al deslizar a la derecha) -->
                <a
                    href="{{ route('products.edit', $product) }}"
                    class="absolute inset-y-0 left-0 bg-blue-600
                           flex flex-col items-center justify-center gap-1
                           text-white text-xs font-semibold
                           focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white
                           transition-opacity"
                    :style="{ width: W + 'px' }"
                    :class="offset > 0 ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                    :tabindex="offset > 0 ? 0 : -1"
                    aria-label="Modificar {{ $product->name }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-6 h-6 shrink-0" viewBox="0 0 16 16">
                        <path d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001"/>
                    </svg>
                    <span>Editar</span>
                </a>

                <!-- ELIMINAR (detrás, a la derecha; se ve al deslizar a la izquierda) -->
                <form
                    method="POST"
                    action="{{ route('products.destroy', $product) }}"
                    onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');"
                    class="absolute inset-y-0 right-0 bg-red-600 transition-opacity"
                    :style="{ width: W + 'px' }"
                    :class="offset < 0 ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                >
                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="w-full h-full flex flex-col items-center justify-center
                               gap-1 text-white text-xs font-semibold
                               focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                        :tabindex="offset < 0 ? 0 : -1"
                        aria-label="Eliminar {{ $product->name }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 shrink-0"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/>
                        </svg>
                        <span>Eliminar</span>
                    </button>
                </form>

                <!-- Tarjeta deslizable -->
                <article
                    class="relative bg-white dark:bg-gray-800 p-3 sm:p-4"
                    :style="{
                        transform: `translateX(${offset}px)`,
                        transition: tracking ? 'none' : 'transform 200ms ease'
                    }"
                    style="touch-action: pan-y;"
                    @pointerdown="start($event)"
                    @pointermove="move($event)"
                    @pointerup="end()"
                    @pointercancel="cancel()"
                >

                    <div class="flex items-center gap-3">

                        <!-- Miniatura -->
                        <div class="shrink-0">
                            @if ($primaryImage)
                                <button
                                    type="button"
                                    class="relative block w-20 h-20 rounded-xl overflow-hidden
                                           bg-gray-100 dark:bg-gray-900
                                           border border-gray-200 dark:border-gray-700
                                           focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    @click="openViewer({{ $primaryIndex }})"
                                    aria-label="Ampliar fotografía de {{ $product->name }}"
                                >
                                    <img
                                        src="{{ $thumb }}"
                                        alt="{{ $primaryImage->alt_text ?? $product->name }}"
                                        width="80" height="80"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                        decoding="async"
                                        draggable="false"
                                    >

                                    @if ($product->images->count() > 1)
                                        <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded-full
                                                     bg-black/70 text-white text-[10px] font-semibold leading-none">
                                            +{{ $product->images->count() - 1 }}
                                        </span>
                                    @endif
                                </button>
                            @else
                                <div
                                    class="w-20 h-20 rounded-xl bg-gray-100 dark:bg-gray-900
                                           border border-dashed border-gray-300 dark:border-gray-700
                                           flex flex-col items-center justify-center text-gray-400"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M3 16l5-5 4 4 4-6 5 7M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                                    </svg>
                                    <span class="text-[10px] mt-0.5">Sin foto</span>
                                </div>
                            @endif
                        </div>

                        <!-- Información -->
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-gray-800 dark:text-white truncate">
                                {{ $product->name }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                               {{ $product->sku }}
                            </p>

                            <div class="mt-2 flex items-end justify-between gap-2">

                                <!-- Existencias y, debajo, el precio -->
                                <div class="min-w-0">
                                    <span class="inline-flex items-center rounded-lg bg-gray-100 dark:bg-gray-700
                                                 px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                     Cantidad: {{ $product->stock}}
                                    </span>
                                    <p class="mt-4 text-base font-bold text-emerald-600 dark:text-emerald-400 leading-tight">
                                        ${{ number_format($product->price, 2) }}
                                    </p>
                                </div>

                                <!-- Imprimir -->

                                <a
                                href="{{ route('products.show', $product) }}"
                                class="shrink-0 inline-flex items-center justify-center w-11 h-11 rounded-xl
                                    border border-sky-200 dark:border-sky-800
                                    bg-white-50 dark:bg-white-950/40
                                    text-sky-600 dark:text-sky-300
                                    hover:bg-sky-100 dark:hover:bg-sky-900/60
                                    hover:border-sky-300 dark:hover:border-sky-700
                                    transition-all duration-200 shadow-sm
                                    focus:outline-none focus:ring-2 focus:ring-sky-400 dark:focus:ring-sky-500"
                                title="Imprimir"
                                aria-label="Imprimir {{ $product->name }}"
                                @click="if (moved) $event.preventDefault()"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-6 h-6" viewBox="0 0 16 16">
                                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1"/>
                                </svg>
                            </a>

                            </div>
                        </div>

                    </div>

                </article>

            </div>

        @empty

            <div class="bg-white dark:bg-gray-800 rounded-2xl p-8
                        text-center text-gray-500 dark:text-gray-400 shadow">
                No se encontraron productos.
            </div>

        @endforelse

    </div>

</div>


<!-- VISOR DE FOTOGRAFÍAS (se teletransporta al <body>) -->
<div
    x-data="{
        open: false,
        images: [],
        index: 0,

        get current() { return this.images[this.index] ?? { src: '', alt: '' }; },

        preload() {
            if (this.images.length < 2) return;
            [1, -1].forEach(d => {
                const i = (this.index + d + this.images.length) % this.images.length;
                new Image().src = this.images[i].src;
            });
        },

        show(e) {
            this.images = e.detail.images;
            this.index = e.detail.index ?? 0;
            this.open = true;
            document.body.classList.add('overflow-hidden');
            this.preload();
        },

        close() {
            this.open = false;
            document.body.classList.remove('overflow-hidden');
        },

        next() { this.index = (this.index + 1) % this.images.length; this.preload(); },
        prev() { this.index = (this.index - 1 + this.images.length) % this.images.length; this.preload(); }
    }"
    @open-product-image.window="show($event)"
    @keydown.escape.window="if (open) close()"
    @keydown.arrow-right.window="if (open && images.length > 1) next()"
    @keydown.arrow-left.window="if (open && images.length > 1) prev()"
>
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[100] flex flex-col bg-black/95"
            role="dialog"
            aria-modal="true"
            aria-label="Fotografía ampliada del producto"
        >
            <!-- Barra superior: cerrar -->
            <div class="shrink-0 flex items-center justify-end px-3 sm:px-5 pb-2"
                 style="padding-top: max(0.75rem, env(safe-area-inset-top));">
                <button
                    type="button"
                    @click="close()"
                    class="flex items-center justify-center w-11 h-11 rounded-full
                           bg-white/15 hover:bg-white/30 text-white border border-white/30
                           focus:outline-none focus:ring-2 focus:ring-white"
                    aria-label="Cerrar fotografía"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <!-- Imagen -->
            <div class="relative flex-1 min-h-0 mx-3 sm:mx-8" @click.self="close()">
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-10 h-10 rounded-full border-4 border-white/20 border-t-white animate-spin"></div>
                </div>

                <img
                    x-bind:src="current.src"
                    x-bind:alt="current.alt"
                    class="absolute inset-0 w-full h-full object-contain select-none"
                    decoding="async"
                    draggable="false"
                >
            </div>

            <!-- Barra inferior: anterior / contador / siguiente -->
            <div
                x-show="images.length > 1"
                class="shrink-0 flex items-center justify-center gap-6 px-4 pt-3"
                style="padding-bottom: max(1rem, env(safe-area-inset-bottom));"
            >
                <button
                    type="button"
                    @click="prev()"
                    class="flex items-center justify-center w-12 h-12 rounded-full
                           bg-white/15 hover:bg-white/30 text-white border border-white/30
                           focus:outline-none focus:ring-2 focus:ring-white"
                    aria-label="Fotografía anterior"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <p class="min-w-[4rem] text-center text-white/80 text-sm tabular-nums"
                   x-text="(index + 1) + ' / ' + images.length"></p>

                <button
                    type="button"
                    @click="next()"
                    class="flex items-center justify-center w-12 h-12 rounded-full
                           bg-white/15 hover:bg-white/30 text-white border border-white/30
                           focus:outline-none focus:ring-2 focus:ring-white"
                    aria-label="Fotografía siguiente"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>

<!-- BOTÓN FLOTANTE PARA AGREGAR PRODUCTO -->
<div class="fixed right-4 sm:right-6 z-40 lg:max-w-5xl lg:mx-auto lg:inset-x-0 pointer-events-none" style="bottom: calc(1rem + env(safe-area-inset-bottom));">
    <div class="relative flex justify-end">
        <a
            href="{{ route('products.create') }}"
            class="group pointer-events-auto w-14 h-14 rounded-full
                   bg-gradient-to-tr from-blue-200 via-teal-500 to-emerald-400
                   dark:from-blue-700 dark:via-teal-600 dark:to-emerald-500
                   text-white shadow-xl shadow-blue-950/30
                   flex items-center justify-center transition-all duration-300
                   hover:from-blue-500 hover:via-teal-400 hover:to-emerald-300
                   dark:hover:from-blue-200 dark:hover:via-teal-500 dark:hover:to-emerald-400
                   hover:shadow-2xl hover:shadow-blue-500/40 hover:scale-110 hover:-translate-y-1
                   focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            title="Agregar producto"
            aria-label="Agregar producto"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 transition-transform duration-300 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
        </a>
    </div>
</div>

</x-app-layout>
