
<x-app-layout>
    <div class="relative min-h-screen bg-gray-100 py-6 transition-colors duration-300 dark:bg-gray-900 sm:py-8">
        <x-background-glow />

        <div class="relative z-10 mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- ENCABEZADO --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                        Historial de ventas
                    </h1>

                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $isBoss
                            ? 'Consulta y administra las ventas del sistema.'
                            : 'Consulta las ventas que has realizado.' }}
                    </p>
                </div>

                <a href="{{ route('sales.index') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-blue-500 to-teal-500
                          px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:scale-[1.02]">
                    + Nueva venta
                </a>
            </div>

            {{-- MENSAJES --}}
            @if (session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800
                            dark:border-green-800 dark:bg-green-900/40 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-700
                            dark:border-red-700 dark:bg-red-900/40 dark:text-red-200">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- BÚSQUEDA Y FECHA --}}
            <form method="GET"
                  action="{{ route('sales.history') }}"
                  x-data="{ search: @js(request('search', '')) }"
                  x-ref="filters"
                  class="rounded-2xl border border-gray-200 bg-white p-3 shadow-sm
                         dark:border-gray-700 dark:bg-gray-800">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">

                    {{-- BUSCADOR --}}
                    <div class="min-w-0 flex-1">

                        <label for="search"
                               class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-300">
                            Buscar venta por folio
                        </label>

                        <div class="relative">

                            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5
                                        -translate-y-1/2 text-gray-400"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <circle cx="11" cy="11" r="7" stroke-width="2"/>
                                <path stroke-linecap="round" stroke-width="2" d="m20 20-4-4"/>
                            </svg>

                            <input
                                id="search"
                                type="text"
                                name="search"
                                x-model="search"
                                @input.debounce.400ms="$refs.filters.requestSubmit()"
                                placeholder="Folio o últimos dígitos..."
                                autocomplete="off"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-3 pl-10 pr-11
                                       text-sm text-gray-800 placeholder:text-gray-400
                                       focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500
                                       dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">

                            {{-- LIMPIAR BÚSQUEDA --}}
                            <button
                                type="button"
                                x-show="search.length > 0"
                                x-cloak
                                @click="search = ''; $nextTick(() => $refs.filters.requestSubmit())"
                                title="Limpiar búsqueda"
                                aria-label="Limpiar búsqueda"
                                class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2
                                       items-center justify-center rounded-full text-gray-400
                                       transition hover:bg-gray-200 hover:text-gray-700
                                       dark:hover:bg-gray-700 dark:hover:text-white">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="h-5 w-5"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">
                            Puedes escribir el folio completo o solamente sus últimos números.
                        </p>
                    </div>

                    {{-- CALENDARIO COMPACTO --}}
                    <div class="flex shrink-0 items-center gap-3 sm:border-l sm:border-gray-200
                                sm:pl-4 dark:sm:border-gray-700">

                        <label class="relative flex h-11 w-11 cursor-pointer items-center justify-center
                                      rounded-xl border border-gray-200 bg-gray-50 text-teal-600
                                      transition hover:bg-teal-50
                                      dark:border-gray-700 dark:bg-gray-900 dark:text-teal-400
                                      dark:hover:bg-gray-700"
                               title="Seleccionar fecha">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 class="h-6 w-6"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <rect x="3" y="5" width="18" height="16" rx="2" stroke-width="1.8"/>
                                <path stroke-linecap="round" stroke-width="1.8"
                                      d="M16 3v4M8 3v4M3 10h18"/>
                            </svg>

                            <input
                                type="date"
                                name="date"
                                value="{{ $selectedDate }}"
                                max="{{ now()->toDateString() }}"
                                aria-label="Seleccionar fecha"
                                onchange="this.form.requestSubmit()"
                                class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                        </label>

                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                Fecha seleccionada
                            </p>

                            <p class="max-w-[190px] truncate text-sm font-bold text-gray-800 dark:text-white">
                                {{ \Carbon\Carbon::parse($selectedDate)->locale('es')->translatedFormat('d M Y') }}
                            </p>

                            @if ($selectedDate === now()->toDateString())
                                <span class="text-[10px] font-semibold text-teal-600 dark:text-teal-400">
                                    Hoy
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- FILTRO DE VENDEDOR: SOLO JEFE --}}
                @if ($isBoss)
                    <div class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700">

                        <label for="seller"
                               class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">
                            Consultar ventas de:
                        </label>

                        <select
                            id="seller"
                            name="seller"
                            onchange="this.form.requestSubmit()"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5
                                   text-sm text-gray-800 focus:border-teal-500 focus:outline-none
                                   focus:ring-2 focus:ring-teal-500
                                   dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:max-w-xs">

                            <option value="">Todos los vendedores</option>

                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}"
                                    @selected((string) request('seller') === (string) $seller->id)>
                                    {{ $seller->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </form>

            {{-- FECHA SELECCIONADA --}}
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Historial correspondiente a
                </p>

                <h2 class="mt-1 text-lg font-bold text-gray-800 dark:text-white">
                    {{ \Carbon\Carbon::parse($selectedDate)->locale('es')->translatedFormat('l, d \d\e F \d\e Y') }}
                </h2>
            </div>

            {{-- RESUMEN: SOLO JEFE --}}
            @if ($isBoss)
                <div class="grid grid-cols-2 gap-3">

                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm
                                dark:border-gray-700 dark:bg-gray-800">

                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            Ventas realizadas
                        </p>

                        <p class="mt-2 text-3xl font-extrabold tabular-nums text-gray-800 dark:text-white">
                            {{ $summary['count'] }}
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            En esta fecha
                        </p>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm
                                dark:border-gray-700 dark:bg-gray-800">

                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            Total vendido
                        </p>

                        <p class="mt-2 text-2xl font-extrabold tabular-nums text-teal-600
                                  dark:text-teal-400 sm:text-3xl">
                            ${{ number_format($summary['total'], 2) }}
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            En esta fecha
                        </p>
                    </div>
                </div>
            @endif

            {{-- ENCABEZADO DE LISTA --}}
            <div class="flex items-center justify-between gap-2 pt-2">
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                        {{ $isBoss ? 'Ventas registradas' : 'Mis ventas' }}
                    </h2>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $sales->total() }}
                        {{ $sales->total() === 1 ? 'venta encontrada' : 'ventas encontradas' }}
                    </p>
                </div>
            </div>



            {{-- LISTA DE VENTAS --}}
            <div class="space-y-3">

                @forelse ($sales as $sale)

                    @if ($isBoss)

                        {{-- TARJETA DESLIZABLE DEL JEFE --}}
                        <div
                            x-data="{
                                W: 96,
                                startX: 0,
                                startOffset: 0,
                                offset: 0,
                                dragging: false,
                                moved: false,

                                start(event) {
                                    if (event.pointerType === 'mouse' && event.button !== 0) return;

                                    this.startX = event.clientX;
                                    this.startOffset = this.offset;
                                    this.dragging = true;
                                    this.moved = false;

                                    if (event.pointerType === 'touch') {
                                        event.currentTarget.setPointerCapture(event.pointerId);
                                    }
                                },

                                move(event) {
                                    if (!this.dragging) return;

                                    const difference = event.clientX - this.startX;

                                    if (Math.abs(difference) > 6) {
                                        this.moved = true;
                                    }

                                    this.offset = Math.max(
                                        -this.W,
                                        Math.min(this.W, this.startOffset + difference)
                                    );
                                },

                                finish() {
                                    if (!this.dragging) return;

                                    this.dragging = false;

                                    if (this.offset > 35) {
                                        this.offset = this.W;
                                    } else if (this.offset < -35) {
                                        this.offset = -this.W;
                                    } else {
                                        this.offset = 0;
                                    }
                                },

                                close() {
                                    this.offset = 0;
                                }
                            }"
                            class="relative overflow-hidden rounded-2xl border border-gray-200
                                   shadow-sm dark:border-gray-700">

                            {{-- EDITAR: DETRÁS A LA IZQUIERDA --}}
                            <a
                                href="{{ route('sales.edit', $sale) }}"
                                class="absolute inset-y-0 left-0 flex flex-col items-center justify-center
                                       gap-1 bg-blue-600 text-xs font-semibold text-white
                                       transition-opacity focus:outline-none focus:ring-2
                                       focus:ring-inset focus:ring-white"
                                :style="{ width: W + 'px' }"
                                :class="offset > 0 ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                :tabindex="offset > 0 ? 0 : -1"
                                aria-label="Editar venta {{ $sale->folio }}">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                     fill="currentColor"
                                     class="h-6 w-6 shrink-0"
                                     viewBox="0 0 16 16">
                                    <path d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001"/>
                                </svg>

                                <span>Editar</span>
                            </a>

                            {{-- ELIMINAR: DETRÁS A LA DERECHA --}}
                            <form
                                method="POST"
                                action="{{ route('sales.destroy', $sale) }}"
                                onsubmit="return confirm('¿Eliminar la venta {{ $sale->folio }}? Las existencias regresarán al inventario. Esta acción no se puede deshacer.');"
                                class="absolute inset-y-0 right-0 bg-red-600 transition-opacity"
                                :style="{ width: W + 'px' }"
                                :class="offset < 0 ? 'opacity-100' : 'opacity-0 pointer-events-none'">

                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="flex h-full w-full flex-col items-center justify-center
                                           gap-1 text-xs font-semibold text-white
                                           focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                                    :tabindex="offset < 0 ? 0 : -1"
                                    aria-label="Eliminar venta {{ $sale->folio }}">

                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         class="h-6 w-6 shrink-0"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/>
                                    </svg>

                                    <span>Eliminar</span>
                                </button>
                            </form>

                            {{-- CONTENIDO DE LA VENTA --}}
                            <article
                                @pointerdown="start($event)"
                                @pointermove="move($event)"
                                @pointerup="finish()"
                                @pointercancel="finish()"
                                @click.capture="
                                    if (moved) {
                                        $event.preventDefault();
                                        $event.stopPropagation();
                                        moved = false;
                                    } else if (offset !== 0) {
                                        $event.preventDefault();
                                        close();
                                    }
                                "
                                :style="{
                                    transform: `translateX(${offset}px)`,
                                    transition: dragging ? 'none' : 'transform 220ms ease'
                                }"
                                class="relative select-none rounded-2xl border border-gray-200
                                       bg-white p-4 dark:border-gray-700 dark:bg-gray-800
                                       touch-pan-y">

                                <div class="flex items-start justify-between gap-3">

                                    <div class="min-w-0 flex-1">

                                        <a href="{{ route('sales.show', $sale) }}"
                                           class="block truncate font-bold text-gray-800
                                                  hover:text-teal-600 dark:text-white">
                                            {{ $sale->folio }}
                                        </a>

                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $sale->created_at->format('d/m/Y H:i') }}
                                            · {{ $sale->user?->name ?? 'Usuario eliminado' }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $sale->items_count }}
                                            {{ $sale->items_count === 1 ? 'producto' : 'productos' }}
                                        </p>

                                        <div class="mt-2 flex flex-wrap gap-1.5">

                                            <span class="rounded-md bg-gray-100 px-2 py-1 text-[11px]
                                                         font-semibold text-gray-700
                                                         dark:bg-gray-700 dark:text-gray-200">
                                                {{ ucfirst($sale->payment_method) }}
                                            </span>

                                            @if ($sale->receipt_path)
                                                <span class="rounded-md bg-blue-50 px-2 py-1 text-[11px]
                                                             font-semibold text-blue-700
                                                             dark:bg-blue-900/50 dark:text-blue-200">
                                                    Con comprobante
                                                </span>
                                            @endif

                                        </div>
                                    </div>

                                    <p class="shrink-0 text-lg font-extrabold tabular-nums
                                              text-gray-900 dark:text-white">
                                        ${{ number_format($sale->total, 2) }}
                                    </p>

                                </div>

                               
                            </article>
                        </div>

                    @else

                        {{-- TARJETA DEL VENDEDOR: SOLO CONSULTA --}}
                        <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm
                                        dark:border-gray-700 dark:bg-gray-800">

                            <div class="flex items-start justify-between gap-3">

                                <div class="min-w-0 flex-1">

                                    <a href="{{ route('sales.show', $sale) }}"
                                       class="block truncate font-bold text-gray-800
                                              hover:text-teal-600 dark:text-white">
                                        {{ $sale->folio }}
                                    </a>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $sale->created_at->format('d/m/Y H:i') }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $sale->items_count }}
                                        {{ $sale->items_count === 1 ? 'producto' : 'productos' }}
                                    </p>

                                    <div class="mt-2 flex flex-wrap gap-1.5">

                                        <span class="rounded-md bg-gray-100 px-2 py-1 text-[11px]
                                                     font-semibold text-gray-700
                                                     dark:bg-gray-700 dark:text-gray-200">
                                            {{ ucfirst($sale->payment_method) }}
                                        </span>

                                        @if ($sale->receipt_path)
                                            <span class="rounded-md bg-blue-50 px-2 py-1 text-[11px]
                                                         font-semibold text-blue-700
                                                         dark:bg-blue-900/50 dark:text-blue-200">
                                                Con comprobante
                                            </span>
                                        @endif

                                    </div>
                                </div>

                                <p class="shrink-0 text-lg font-extrabold tabular-nums
                                          text-gray-900 dark:text-white">
                                    ${{ number_format($sale->total, 2) }}
                                </p>

                            </div>


                        </article>

                    @endif

                @empty

                    {{-- SIN RESULTADOS --}}
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white
                                px-5 py-12 text-center dark:border-gray-600 dark:bg-gray-800">

                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full
                                    bg-gray-100 text-gray-400 dark:bg-gray-700">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 class="h-7 w-7"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <rect x="3" y="5" width="18" height="16" rx="2" stroke-width="1.8"/>
                                <path stroke-linecap="round" stroke-width="1.8"
                                      d="M16 3v4M8 3v4M3 10h18"/>
                            </svg>
                        </div>

                        <h3 class="mt-4 font-bold text-gray-800 dark:text-white">
                            No hay ventas para mostrar
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            No encontramos ventas que coincidan con tu búsqueda o la fecha seleccionada.
                        </p>

                        <a href="{{ route('sales.history', ['date' => $selectedDate]) }}"
                           class="mt-4 inline-flex rounded-xl border border-gray-200 px-4 py-2.5
                                  text-sm font-semibold text-gray-700
                                  dark:border-gray-600 dark:text-gray-200">
                            Ver todas las ventas de este día
                        </a>
                    </div>

                @endforelse
            </div>

            {{-- PAGINACIÓN --}}
            @if ($sales->hasPages())
                <div class="pt-2">
                    {{ $sales->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
