@php
    use Carbon\Carbon;

    // ── Filtros activos ────────────────────────────────────────────
    $from = Carbon::parse($filters['from'])->toDateString();
    $to = Carbon::parse($filters['to'])->toDateString();
    $type = $filters['type'] ?? null;
    $search = (string) ($filters['search'] ?? '');

    $today = now()->toDateString();
    $ranges = [
        'today' => ['Hoy', $today, $today],
        'week' => ['Semana', now()->subDays(6)->toDateString(), $today],
        'month30' => ['Mes', now()->subDays(29)->toDateString(), $today],
    ];

    $activeRange = 'custom';
    foreach ($ranges as $key => [$rLabel, $rFrom, $rTo]) {
        if ($from === $rFrom && $to === $rTo) {
            $activeRange = $key;
            break;
        }
    }

    // Parámetros que se conservan al cambiar un solo filtro
    $params = fn (array $extra = []) => array_filter(
        array_merge(['from' => $from, 'to' => $to, 'type' => $type, 'search' => $search], $extra),
        fn ($v) => $v !== null && $v !== ''
    );

    $hasExtraFilters = $type || $search !== '';
    $periodDays = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;

    // ── Indicadores ────────────────────────────────────────────────
    $totalMovements = (int) $totalMovements;
    $unitsIn = (int) $unitsIn;
    $unitsOut = (int) $unitsOut;
    $net = $unitsIn - $unitsOut;
    $flow = max(1, $unitsIn + $unitsOut);
    $inPct = round($unitsIn / $flow * 100);

    $chip = fn (bool $active) => 'inline-flex min-h-[44px] shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-teal-500 '
        . ($active
            ? 'border-teal-500 bg-teal-50 text-teal-700 dark:bg-teal-900/40 dark:text-teal-200'
            : 'border-gray-200 bg-white text-gray-600 hover:border-teal-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300');

    // Etiqueta amigable para el encabezado de cada día
    $dayLabel = function (string $date) {
        if ($date === 'sin-fecha') {
            return 'Sin fecha';
        }
        $d = Carbon::parse($date);

        return $d->isToday() ? 'Hoy' : ($d->isYesterday() ? 'Ayer' : $d->format('d/m/Y'));
    };

    // Referencia al documento que originó el movimiento (venta, etc.)
    $refOf = function ($movement) {
        if (empty($movement->reference_type) || empty($movement->reference_id)) {
            return null;
        }
        $model = class_basename($movement->reference_type);

        return [
            'label' => ($model === 'Sale' ? 'Venta' : $model) . ' #' . $movement->reference_id,
            'url' => $model === 'Sale' ? route('sales.show', $movement->reference_id) : null,
        ];
    };

    $grouped = $movements->getCollection()->groupBy(fn ($m) => $m->created_at?->toDateString() ?? 'sin-fecha');
@endphp

<x-app-layout>
    <div class="relative min-h-screen bg-gray-100 transition-colors duration-300 dark:bg-gray-900"
         x-data="{ loading: false }"
         @click="if ($event.target.closest('a[data-load]')) loading = true"
         @pageshow.window="loading = false"
         @keydown.window.slash="if (!['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); document.getElementById('search')?.focus(); }">
        <x-background-glow />

        {{-- Barra de carga --}}
        <div x-show="loading" x-cloak class="fixed inset-x-0 top-0 z-50 h-1 overflow-hidden bg-teal-100 print:hidden" role="progressbar" aria-label="Cargando reporte">
            <div class="h-full w-1/3 bg-teal-500" style="animation: report-slide 1s ease-in-out infinite"></div>
        </div>

        <div class="relative mx-auto max-w-7xl space-y-5 px-4 py-5 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

            {{-- ENCABEZADO --}}
            <header class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="mt-0.5 text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">Reportes de Movimientos</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ Carbon::parse($from)->format('d/m/Y') }} – {{ Carbon::parse($to)->format('d/m/Y') }}
                        · {{ $periodDays }} {{ $periodDays === 1 ? 'día' : 'días' }}
                    </p>
                </div>

                <div class="flex shrink-0 gap-2 print:hidden">


                    <a href="{{ route('reports.movements.export', request()->query()) }}" aria-label="Exportar CSV"
                       class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-teal-600 px-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900 sm:px-4">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l4-4m-4 4l-4-4M4 17v4h16v-4"/>
                        </svg>
                        <span class="hidden sm:inline">Exportar CSV</span>
                    </a>
                </div>
            </header>

            {{-- PESTAÑAS DE REPORTES --}}
            <nav class="-mx-4 overflow-x-auto px-4 print:hidden sm:mx-0 sm:px-0" aria-label="Tipo de reporte">
                <div class="inline-flex rounded-2xl border border-gray-200 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <a href="{{ route('reports.inventory') }}" data-load
                       class="rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:text-teal-600 dark:text-gray-300 dark:hover:text-teal-300">
                        Inventario
                    </a>
                    <a href="{{ route('reports.movements') }}" aria-current="page"
                       class="rounded-xl bg-gradient-to-r from-blue-500 to-teal-500 px-4 py-2.5 text-sm font-semibold text-white shadow">
                        Movimientos
                    </a>
                </div>
            </nav>

            {{-- FILTROS --}}
            <section class="rounded-2xl border border-gray-200 bg-white/90 p-4 shadow-sm backdrop-blur dark:border-gray-700 dark:bg-gray-800/90 sm:p-5 print:hidden"
                     x-data="{ custom: {{ $activeRange === 'custom' ? 'true' : 'false' }} }">

                {{-- Periodo --}}
                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Periodo</p>

                <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($ranges as $key => [$rLabel, $rFrom, $rTo])
                        <a href="{{ route('reports.movements', $params(['from' => $rFrom, 'to' => $rTo])) }}" data-load
                           @if ($activeRange === $key) aria-current="true" @endif
                           class="{{ $chip($activeRange === $key) }}">
                            {{ $rLabel }}
                        </a>
                    @endforeach

                    <button type="button" @click="custom = !custom" :aria-expanded="custom.toString()"
                            :class="custom ? 'border-teal-500 bg-teal-50 text-teal-700 dark:bg-teal-900/40 dark:text-teal-200' : 'border-gray-200 bg-white text-gray-600 hover:border-teal-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                            class="inline-flex min-h-[44px] shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('reports.movements') }}" method="GET" x-show="custom" x-cloak x-transition
                      @submit="loading = true"
                      class="mt-3 grid grid-cols-2 gap-3 sm:max-w-xl sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                    @if ($type) <input type="hidden" name="type" value="{{ $type }}"> @endif
                    @if ($search !== '') <input type="hidden" name="search" value="{{ $search }}"> @endif

                    <div>
                        <label for="from" class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-300">Desde</label>
                        <input type="date" id="from" name="from" value="{{ $from }}" max="{{ $to }}"
                               class="h-11 w-full rounded-xl border-gray-300 bg-gray-50 text-base text-gray-800 focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label for="to" class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-300">Hasta</label>
                        <input type="date" id="to" name="to" value="{{ $to }}" min="{{ $from }}" max="{{ $today }}"
                               class="h-11 w-full rounded-xl border-gray-300 bg-gray-50 text-base text-gray-800 focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>

                    <button type="submit"
                            class="col-span-2 h-11 rounded-xl bg-teal-600 px-5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-400 sm:col-span-1">
                        Aplicar
                    </button>
                </form>

                {{-- Tipo --}}
                <p class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tipo de movimiento</p>

                <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <a href="{{ route('reports.movements', $params(['type' => null])) }}" data-load
                       class="{{ $chip(!$type) }}">Todos</a>
                    <a href="{{ route('reports.movements', $params(['type' => 'entrada'])) }}" data-load
                       class="{{ $chip($type === 'entrada') }}">
                        <span class="text-emerald-500" aria-hidden="true">↑</span> Entradas
                    </a>
                    <a href="{{ route('reports.movements', $params(['type' => 'salida'])) }}" data-load
                       class="{{ $chip($type === 'salida') }}">
                        <span class="text-red-500" aria-hidden="true">↓</span> Salidas
                    </a>
                </div>

                {{-- Búsqueda --}}


                @if ($hasExtraFilters)
                    <div class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                        <a href="{{ route('reports.movements', ['from' => $from, 'to' => $to]) }}" data-load
                           class="inline-flex min-h-[44px] items-center gap-1 text-sm font-semibold text-gray-600 transition hover:text-red-600 dark:text-gray-300 dark:hover:text-red-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-width="2.5" stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Quitar tipo y búsqueda
                        </a>
                    </div>
                @endif
            </section>

            {{-- HISTORIAL --}}
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-labelledby="list-title">
                <div class="flex flex-col gap-1 border-b border-gray-100 p-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <div>
                        <h2 id="list-title" class="font-bold text-gray-900 dark:text-white">Historial de movimientos</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400" aria-live="polite">
                            @if ($movements->total() > 0)
                                Mostrando {{ $movements->firstItem() }}–{{ $movements->lastItem() }} de {{ number_format($movements->total()) }}
                            @else
                                Sin resultados
                            @endif
                        </p>
                    </div>
                    <span class="text-xs text-gray-400">Página {{ $movements->currentPage() }} de {{ max(1, $movements->lastPage()) }}</span>
                </div>

                @if ($movements->count())

                    {{-- Línea de tiempo (móvil y tableta) --}}
                    <div class="lg:hidden">
                        @foreach ($grouped as $date => $items)
                            <div class="flex items-center justify-between bg-gray-50 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-900/60 dark:text-gray-400">
                                <span>{{ $dayLabel($date) }}</span>
                                <span class="font-normal normal-case">{{ $items->count() }} {{ $items->count() === 1 ? 'movimiento' : 'movimientos' }}</span>
                            </div>

                            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($items as $movement)
                                    @php
                                        $isIn = $movement->type === 'entrada';
                                        $reason = trim((string) $movement->reason);
                                        $longReason = mb_strlen($reason) > 70;
                                        $ref = $refOf($movement);
                                    @endphp
                                    <li class="p-4" x-data="{ open: false }">
                                        <div class="flex items-start gap-3">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg font-bold {{ $isIn ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300' }}" aria-hidden="true">
                                                {{ $isIn ? '↑' : '↓' }}
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                <p class="font-semibold leading-snug text-gray-900 dark:text-white">{{ $movement->product?->name ?? 'Producto eliminado' }}</p>
                                                <p class="font-mono text-xs text-gray-400">{{ $movement->product?->sku ?? 'Sin SKU' }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $movement->created_at?->format('H:i') }} · {{ $movement->user?->name ?? 'Usuario eliminado' }}
                                                </p>
                                            </div>

                                            <div class="shrink-0 text-right">
                                                <p class="text-xl font-extrabold leading-none tabular-nums {{ $isIn ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $isIn ? '+' : '−' }}{{ number_format($movement->quantity) }}
                                                </p>
                                                <p class="mt-1.5 whitespace-nowrap text-xs tabular-nums text-gray-500 dark:text-gray-400">
                                                    {{ number_format($movement->stock_before) }} → <strong class="text-gray-800 dark:text-gray-100">{{ number_format($movement->stock_after) }}</strong>
                                                </p>
                                            </div>
                                        </div>

                                        @if ($reason !== '' || $ref)
                                            <div class="mt-3 rounded-xl bg-gray-50 p-3 text-sm dark:bg-gray-900/50">
                                                @if ($reason !== '')
                                                    <p class="break-words text-gray-600 dark:text-gray-300" @if ($longReason) :class="open ? '' : 'line-clamp-2'" @endif>{{ $reason }}</p>

                                                    @if ($longReason)
                                                        <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                                                                class="mt-1 min-h-[32px] text-xs font-semibold text-teal-600 hover:underline dark:text-teal-400">
                                                            <span x-text="open ? 'Ver menos' : 'Ver más'"></span>
                                                        </button>
                                                    @endif
                                                @endif

                                                @if ($ref)
                                                    <p class="{{ $reason !== '' ? 'mt-1' : '' }} text-xs text-gray-400">
                                                        Ref.
                                                        @if ($ref['url'])
                                                            <a href="{{ $ref['url'] }}" class="font-semibold text-teal-600 hover:underline dark:text-teal-400">{{ $ref['label'] }}</a>
                                                        @else
                                                            {{ $ref['label'] }}
                                                        @endif
                                                    </p>
                                                @endif
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>

                    {{-- Tabla (escritorio) --}}
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:bg-gray-900/60 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-semibold">Fecha</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">Producto</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">Tipo</th>
                                    <th scope="col" class="px-4 py-3 text-right font-semibold">Cantidad</th>
                                    <th scope="col" class="px-4 py-3 text-right font-semibold">Stock</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">Responsable y motivo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($movements as $movement)
                                    @php
                                        $isIn = $movement->type === 'entrada';
                                        $reason = trim((string) $movement->reason);
                                        $ref = $refOf($movement);
                                    @endphp
                                    <tr class="align-top transition hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $movement->created_at?->format('d/m/Y') }}</p>
                                            <p class="text-xs text-gray-400">{{ $movement->created_at?->format('H:i') }}</p>
                                        </td>

                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $movement->product?->name ?? 'Producto eliminado' }}</p>
                                            <p class="font-mono text-xs text-gray-400">{{ $movement->product?->sku ?? 'Sin SKU' }}</p>
                                        </td>

                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $isIn ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300' }}">
                                                <span aria-hidden="true">{{ $isIn ? '↑' : '↓' }}</span> {{ $isIn ? 'Entrada' : 'Salida' }}
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right text-base font-bold tabular-nums {{ $isIn ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $isIn ? '+' : '−' }}{{ number_format($movement->quantity) }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-500 dark:text-gray-400">
                                            {{ number_format($movement->stock_before) }}
                                            <span class="text-gray-300" aria-hidden="true">→</span>
                                            <strong class="text-gray-900 dark:text-white">{{ number_format($movement->stock_after) }}</strong>
                                        </td>

                                        <td class="max-w-xs px-4 py-3">
                                            <p class="font-medium text-gray-800 dark:text-gray-200">{{ $movement->user?->name ?? 'Usuario eliminado' }}</p>
                                            <p class="break-words text-xs text-gray-500 dark:text-gray-400">{{ $reason !== '' ? $reason : 'Sin motivo especificado' }}</p>
                                            @if ($ref)
                                                <p class="mt-0.5 text-xs text-gray-400">
                                                    Ref.
                                                    @if ($ref['url'])
                                                        <a href="{{ $ref['url'] }}" class="font-semibold text-teal-600 hover:underline dark:text-teal-400">{{ $ref['label'] }}</a>
                                                    @else
                                                        {{ $ref['label'] }}
                                                    @endif
                                                </p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-14 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700" aria-hidden="true">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 4v6h6M20 20v-6h-6"/><path d="M5.5 15a7 7 0 0 0 12 2M18.5 9a7 7 0 0 0-12-2"/></svg>
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">No hay movimientos en este periodo</h3>
                        <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">Prueba con otro periodo o quita el tipo y la búsqueda.</p>
                        <div class="mt-5 flex flex-wrap justify-center gap-2 print:hidden">
                            <a href="{{ route('reports.movements', ['from' => $ranges['month30'][1], 'to' => $ranges['month30'][2]]) }}" data-load
                               class="inline-flex min-h-[44px] items-center rounded-xl bg-teal-600 px-5 text-sm font-semibold text-white transition hover:bg-teal-700">
                                Ver últimos 30 días
                            </a>
                            @if ($hasExtraFilters)
                                <a href="{{ route('reports.movements', ['from' => $from, 'to' => $to]) }}" data-load
                                   class="inline-flex min-h-[44px] items-center rounded-xl border border-gray-300 px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                    Quitar tipo y búsqueda
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($movements->hasPages())
                    <div class="border-t border-gray-100 p-4 dark:border-gray-700 print:hidden">
                        {{ $movements->links() }}
                    </div>
                @endif
            </section>

            <p class="pb-4 text-center text-xs text-gray-400">
                Antoys · Movimientos de inventario · {{ now()->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>

  <style>
    @keyframes report-slide {
        0%   { transform: translateX(-100%); }
        100% { transform: translateX(300%); }
    }

    @media (prefers-reduced-motion: reduce) {
        .transition, .transition-all { transition: none !important; }
    }
</style>
   <script>
    document.addEventListener('alpine:init', () => {
        // Número que sube de 0 al valor final (respeta "reducir movimiento").
        // mode: '' (entero), '+' / '−' (prefijo fijo) o 'signed' (con signo según el valor)
        Alpine.data('countUp', (target, mode = '') => ({
            text: '',
            init() {
                const nf = new Intl.NumberFormat('es-MX');
                const fmt = v => {
                    const n = Math.round(v);
                    if (mode === 'signed') return (n > 0 ? '+' : '') + nf.format(n);
                    return (mode === '+' || mode === '−' ? mode : '') + nf.format(Math.abs(n));
                };

                this.text = fmt(target);

                if (!target || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

                const start = performance.now();
                const duration = 800;
                const step = now => {
                    const t = Math.min(1, (now - start) / duration);
                    this.text = fmt(target * (1 - Math.pow(1 - t, 3)));
                    if (t < 1) requestAnimationFrame(step);
                };
                requestAnimationFrame(step);
            },
        }));
    });
</script>
</x-app-layout>
