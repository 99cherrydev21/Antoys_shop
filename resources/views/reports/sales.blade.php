
@php
    use Carbon\Carbon;

    $currency = fn ($value) => '$' . number_format((float) $value, 2);

    // Filtros activos
    $from = Carbon::parse($filters['from'])->toDateString();
    $to = Carbon::parse($filters['to'])->toDateString();
    $sellerId = $filters['seller_id'] ?? null;
    $payment = $filters['payment_method'] ?? null;

    $today = now()->toDateString();

    $ranges = [
        'today' => ['Hoy', $today, $today],
        'week' => ['Semana', now()->startOfWeek()->toDateString(), $today],
        'month' => ['Mes', now()->startOfMonth()->toDateString(), $today],
    ];

    $activeRange = 'custom';

    foreach ($ranges as $key => [$rLabel, $rFrom, $rTo]) {
        if ($from === $rFrom && $to === $rTo) {
            $activeRange = $key;
            break;
        }
    }

    $keepParams = array_filter([
        'seller_id' => $sellerId,
        'payment_method' => $payment,
    ]);

    $periodDays = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;

    $chip = fn (bool $active) =>
        'inline-flex min-h-[44px] shrink-0 items-center rounded-full border px-4 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-teal-500 '
        . ($active
            ? 'border-teal-500 bg-teal-50 text-teal-700 dark:bg-teal-900/40 dark:text-teal-200'
            : 'border-gray-200 bg-white text-gray-600 hover:border-teal-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300');

    // Indicadores
    $totalSales = (int) $totalSales;
    $totalAmount = (float) $totalAmount;
    $average = $totalSales > 0 ? $totalAmount / $totalSales : 0;

    $days = $salesByDay->map(function ($d) {
        $date = Carbon::parse($d->date);

        return [
            'label' => $date->format('d/m'),
            'full' => $date->format('d/m/Y'),
            'amount' => (float) $d->amount,
            'count' => (int) $d->sales_count,
        ];
    })->values();

    $bestDay = $days->sortByDesc('amount')->first();

    // Línea de tendencia
    $spark = '';

    if ($days->count() > 1) {
        $sparkMax = max(1, (float) $days->max('amount'));
        $n = $days->count();

        foreach ($days as $i => $d) {
            $x = round($i / ($n - 1) * 100, 2);
            $y = round(28 - ($d['amount'] / $sparkMax) * 26, 2);

            $spark .= $x . ',' . $y . ' ';
        }
    }

    // Métodos de pago
    $paymentMeta = [
        'efectivo' => ['Efectivo', '#14b8a6'],
        'tarjeta' => ['Tarjeta', '#3b82f6'],
        'transferencia' => ['Transferencia', '#8b5cf6'],
    ];

    $payTotal = max(0.01, (float) $salesByPayment->sum('amount'));

    $payments = $salesByPayment->map(function ($p) use ($paymentMeta, $payTotal) {
        [$name, $color] = $paymentMeta[$p->payment_method]
            ?? [ucfirst($p->payment_method ?? 'Otro'), '#9ca3af'];

        return [
            'name' => $name,
            'color' => $color,
            'amount' => (float) $p->amount,
            'count' => (int) $p->sales_count,
            'pct' => round((float) $p->amount / $payTotal * 100, 1),
        ];
    })->values();

    $maxSeller = max(1, (float) $salesBySeller->max('amount'));
    $maxUnits = max(1, (int) $topProducts->max('units'));

    // Texto de búsqueda
    $searchRows = $sales->getCollection()->map(
        fn ($s) => mb_strtolower(
            ($s->folio ?? '') . ' ' .
            ($s->user?->name ?? '') . ' ' .
            ($s->payment_method ?? '')
        )
    )->values();
@endphp

<x-app-layout>
    <div
        class="relative min-h-screen bg-gray-100 transition-colors duration-300 dark:bg-gray-900"
        x-data="{ loading: false }"
        @click="if ($event.target.closest('a[data-load]')) loading = true"
        @pageshow.window="loading = false"
    >
        <x-background-glow />

        {{-- Barra de carga --}}
        <div
            x-show="loading"
            x-cloak
            class="fixed inset-x-0 top-0 z-50 h-1 overflow-hidden bg-teal-100 print:hidden"
            role="progressbar"
            aria-label="Cargando reporte"
        >
            <div
                class="h-full w-1/3 bg-teal-500"
                style="animation: report-slide 1s ease-in-out infinite"
            ></div>
        </div>

        <div class="relative mx-auto max-w-7xl space-y-5 px-4 py-5 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

            {{-- ENCABEZADO --}}
            <header class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="mt-0.5 text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">
                        Reportes de Ventas
                    </h1>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ Carbon::parse($from)->format('d/m/Y') }}
                        –
                        {{ Carbon::parse($to)->format('d/m/Y') }}
                        · {{ $periodDays }} {{ $periodDays === 1 ? 'día' : 'días' }}
                    </p>
                </div>

                <div class="flex shrink-0 gap-2 print:hidden">

                    <a
                        href="{{ route('reports.sales.export', request()->query()) }}"
                        aria-label="Exportar CSV"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-teal-600 px-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900 sm:px-4"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l4-4m-4 4l-4-4M4 17v4h16v-4"/>
                        </svg>
                        <span class="hidden sm:inline">Exportar CSV</span>
                    </a>
                </div>
            </header>

            {{-- FILTROS --}}
            <section
                class="rounded-2xl border border-gray-200 bg-white/90 p-4 shadow-sm backdrop-blur dark:border-gray-700 dark:bg-gray-800/90 sm:p-5 print:hidden"
                x-data="{ custom: {{ $activeRange === 'custom' ? 'true' : 'false' }} }"
            >
                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Periodo
                </p>

                <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($ranges as $key => [$rLabel, $rFrom, $rTo])
                        <a
                            href="{{ route('reports.sales', array_merge($keepParams, ['from' => $rFrom, 'to' => $rTo])) }}"
                            data-load
                            @if ($activeRange === $key) aria-current="true" @endif
                            class="{{ $chip($activeRange === $key) }}"
                        >
                            {{ $rLabel }}
                        </a>
                    @endforeach

                    <button
                        type="button"
                        @click="custom = !custom"
                        :aria-expanded="custom.toString()"
                        :class="custom
                            ? 'border-teal-500 bg-teal-50 text-teal-700 dark:bg-teal-900/40 dark:text-teal-200'
                            : 'border-gray-200 bg-white text-gray-600 hover:border-teal-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                        class="inline-flex min-h-[44px] shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-teal-500"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/>
                        </svg>
                    </button>
                </div>

                {{-- Rango personalizado --}}
                <form
                    action="{{ route('reports.sales') }}"
                    method="GET"
                    x-show="custom"
                    x-cloak
                    x-transition
                    @submit="loading = true"
                    class="mt-3 grid grid-cols-2 gap-3 sm:max-w-xl sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                >
                    @foreach ($keepParams as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach

                    <div>
                        <label for="from" class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-300">
                            Desde
                        </label>
                        <input
                            type="date"
                            id="from"
                            name="from"
                            value="{{ $from }}"
                            max="{{ $today }}"
                            class="h-11 w-full rounded-xl border-gray-300 bg-gray-50 text-base text-gray-800 focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label for="to" class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-300">
                            Hasta
                        </label>
                        <input
                            type="date"
                            id="to"
                            name="to"
                            value="{{ $to }}"
                            max="{{ $today }}"
                            class="h-11 w-full rounded-xl border-gray-300 bg-gray-50 text-base text-gray-800 focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        >
                    </div>

                    <button
                        type="submit"
                        class="col-span-2 h-11 rounded-xl bg-teal-600 px-5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-400 sm:col-span-1"
                    >
                        Aplicar
                    </button>
                </form>
            </section>

            {{-- RESUMEN DE VENTAS --}}
            <section class="grid grid-cols-1 gap-4 lg:grid-cols-5">

                {{-- TOTAL VENDIDO --}}
                <article class="relative min-w-0 overflow-hidden rounded-2xl border border-teal-100 bg-white p-5 shadow-sm dark:border-teal-900/60 dark:bg-gray-800 lg:col-span-3">
                    <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-teal-500/10"></div>

                    <div class="relative flex h-full flex-col justify-between">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Total vendido
                                </p>

                                <p
                                    class="mt-2 text-3xl font-extrabold tabular-nums text-gray-900 dark:text-white sm:text-4xl"
                                    x-data="countUp({{ $totalAmount }}, 'money')"
                                    x-text="text"
                                >
                                    {{ $currency($totalAmount) }}
                                </p>

                                <p class="mt-1 text-xs text-gray-400">
                                    Importe acumulado del periodo
                                </p>
                            </div>

                            <span class="rounded-xl bg-teal-100 p-3 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2 0-3 1-3 2s1 2 3 2 3 1 3 2-1 2-3 2m0-10V6m0 12v-2m8-4a8 8 0 11-16 0 8 8 0 0116 0z"/>
                                </svg>
                            </span>
                        </div>

                        @if ($spark)
                            <svg viewBox="0 0 100 30" preserveAspectRatio="none" class="mt-5 h-12 w-full text-teal-500" aria-hidden="true">
                                <polyline
                                    points="{{ trim($spark) }}"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linejoin="round"
                                    stroke-linecap="round"
                                    vector-effect="non-scaling-stroke"
                                />
                            </svg>
                        @endif
                    </div>
                </article>

                {{-- MÉTODOS DE PAGO --}}
                <article
                    x-data="paymentDonut(@js($payments), {{ $payTotal }})"
                    class="min-w-0 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:col-span-2"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h2 class="font-bold text-gray-900 dark:text-white">
                                Métodos de pago
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Distribución por importe
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-col items-center gap-3 sm:flex-row sm:items-center sm:justify-around">
                        {{-- DONA --}}
                        <div class="relative h-36 w-36 shrink-0 sm:h-40 sm:w-40">
                            <svg viewBox="0 0 36 36" class="h-full w-full -rotate-90" role="img" aria-label="Gráfica de métodos de pago">
                                <circle
                                    cx="18"
                                    cy="18"
                                    r="15.9155"
                                    fill="none"
                                    stroke-width="4"
                                    class="stroke-gray-100 dark:stroke-gray-700"
                                />

                                @php $offset = 0; @endphp

                                @foreach ($payments as $i => $p)
                                    <circle
                                        cx="18"
                                        cy="18"
                                        r="15.9155"
                                        fill="none"
                                        stroke="{{ $p['color'] }}"
                                        stroke-dasharray="{{ $p['pct'] }} {{ 100 - $p['pct'] }}"
                                        stroke-dashoffset="{{ -$offset }}"
                                        :stroke-width="hover === {{ $i }} ? 5.5 : 4"
                                        @mouseenter="hover = {{ $i }}"
                                        @mouseleave="hover = null"
                                        class="cursor-pointer transition-all duration-200"
                                        :class="hover !== null && hover !== {{ $i }} ? 'opacity-40' : ''"
                                    />
                                    @php $offset += $p['pct']; @endphp
                                @endforeach
                            </svg>

                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span
                                    class="max-w-[90px] truncate text-[10px] text-gray-500 dark:text-gray-400"
                                    x-text="hover === null ? 'Total' : items[hover].name"
                                ></span>

                                <span
                                    class="text-sm font-extrabold tabular-nums text-gray-900 dark:text-white"
                                    x-text="money(hover === null ? total : items[hover].amount)"
                                ></span>

                                <span
                                    class="text-[10px] text-gray-400"
                                    x-show="hover !== null"
                                    x-text="items[hover]?.pct + '%'"
                                ></span>
                            </div>
                        </div>

                        {{-- LEYENDA --}}
                        <ul class="w-full min-w-0 space-y-1 sm:flex-1">
                            @forelse ($payments as $i => $p)
                                <li>
                                    <button
                                        type="button"
                                        @mouseenter="hover = {{ $i }}"
                                        @mouseleave="hover = null"
                                        @focus="hover = {{ $i }}"
                                        @blur="hover = null"
                                        class="flex min-h-10 w-full items-center gap-2 rounded-lg px-2 text-left transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:hover:bg-gray-700/40"
                                    >
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $p['color'] }}"></span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-xs font-semibold text-gray-800 dark:text-white">
                                                {{ $p['name'] }}
                                            </span>
                                            <span class="block text-[10px] text-gray-400">
                                                {{ $p['count'] }} {{ $p['count'] === 1 ? 'operación' : 'operaciones' }}
                                                · {{ $p['pct'] }}%
                                            </span>
                                        </span>

                                        <span class="shrink-0 text-xs font-bold tabular-nums text-gray-900 dark:text-white">
                                            {{ $currency($p['amount']) }}
                                        </span>
                                    </button>
                                </li>
                            @empty
                                <li class="py-4 text-center text-xs text-gray-400">
                                    No hay pagos registrados.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </article>
            </section>

            {{-- DETALLE DE VENTAS DESPLEGABLE --}}
            <section
                x-data="{ expanded: false }"
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                {{-- ENCABEZADO DEL DESPLEGABLE --}}
                <button
                    type="button"
                    @click="expanded = !expanded"
                    :aria-expanded="expanded"
                    aria-controls="sales-detail-content"
                    class="flex w-full flex-col gap-3 p-4 text-left transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-500 dark:hover:bg-gray-700/30 sm:flex-row sm:items-center sm:justify-between sm:p-5"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l4-4 4 4 6-7"/>
                            </svg>
                        </div>

                        <div>
                            <h2 class="font-bold text-gray-900 dark:text-white">
                                Detalle de ventas
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Operaciones incluidas en este reporte.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 sm:justify-end">
                        <span
                            class="text-xs font-medium text-teal-600 dark:text-teal-400"
                            x-text="expanded ? 'Ocultar ventas' : 'Mostrar ventas'"
                        ></span>

                        <svg
                            class="h-5 w-5 shrink-0 text-gray-500 transition-transform duration-300 dark:text-gray-400"
                            :class="{ 'rotate-180': expanded }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                        </svg>
                    </div>
                </button>

                {{-- CONTENIDO DESPLEGABLE --}}
                <div
                    id="sales-detail-content"
                    x-show="expanded"
                    x-cloak
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                >
                    <div
                        x-data="salesList(@js($searchRows))"
                        class="border-t border-gray-100 dark:border-gray-700"
                    >
                        {{-- BUSCADOR --}}
                        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                    Busca una operación
                                </p>
                                <p class="text-xs text-gray-400">
                                    Puedes buscar por folio, vendedor o método de pago.
                                </p>
                            </div>

                            <div class="relative w-full sm:w-72 print:hidden">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M11 19a8 8 0 110-16 8 8 0 010 16z"/>
                                </svg>

                                <input
                                    type="search"
                                    x-model="q"
                                    placeholder="Buscar folio, vendedor o pago..."
                                    aria-label="Buscar en esta página"
                                    class="h-11 w-full rounded-xl border-gray-300 bg-gray-50 pl-10 text-base text-gray-800 focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white sm:text-sm"
                                >
                            </div>
                        </div>

                        {{-- TARJETAS MÓVILES --}}
                        <ul class="divide-y divide-gray-100 dark:divide-gray-700 md:hidden">
                            @foreach ($sales as $sale)
                                @php
                                    $color = $paymentMeta[$sale->payment_method][1] ?? '#9ca3af';
                                    $methodName = $paymentMeta[$sale->payment_method][0] ?? ucfirst($sale->payment_method ?? 'Otro');
                                @endphp

                                <li x-show="show({{ $loop->index }})">
                                    <a
                                        href="{{ route('sales.show', $sale) }}"
                                        class="flex items-center gap-3 p-4 transition hover:bg-gray-50 focus:bg-gray-50 focus:outline-none dark:hover:bg-gray-700/30 dark:focus:bg-gray-700/30"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-semibold text-gray-900 dark:text-white">
                                                {{ $sale->folio }}
                                            </p>

                                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $sale->created_at->format('d/m/Y H:i') }}
                                                · {{ $sale->user?->name ?? 'Usuario eliminado' }}
                                            </p>

                                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                    <span class="h-2 w-2 rounded-full" style="background: {{ $color }}"></span>
                                                    {{ $methodName }}
                                                </span>

                                                <span class="text-xs text-gray-400">
                                                    {{ number_format($sale->items->sum('quantity')) }} uds.
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-1">
                                            <span class="text-base font-bold tabular-nums text-gray-900 dark:text-white">
                                                {{ $currency($sale->total) }}
                                            </span>

                                            <svg class="h-5 w-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        {{-- TABLA ESCRITORIO --}}
                        <div class="hidden overflow-x-auto md:block">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900/60 dark:text-gray-400">
                                    <tr>
                                        <th class="px-5 py-3">Fecha</th>
                                        <th class="px-5 py-3">Folio</th>
                                        <th class="px-5 py-3">Vendedor</th>
                                        <th class="px-5 py-3">Pago</th>
                                        <th class="px-5 py-3 text-center">Unidades</th>
                                        <th class="px-5 py-3 text-right">Total</th>
                                        <th class="px-5 py-3 text-center print:hidden">Detalle</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($sales as $sale)
                                        @php
                                            $color = $paymentMeta[$sale->payment_method][1] ?? '#9ca3af';
                                            $methodName = $paymentMeta[$sale->payment_method][0] ?? ucfirst($sale->payment_method ?? 'Otro');
                                        @endphp

                                        <tr
                                            x-show="show({{ $loop->index }})"
                                            class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                        >
                                            <td class="whitespace-nowrap px-5 py-3 text-gray-600 dark:text-gray-300">
                                                {{ $sale->created_at->format('d/m/Y H:i') }}
                                            </td>

                                            <td class="px-5 py-3 font-semibold text-gray-900 dark:text-white">
                                                {{ $sale->folio }}
                                            </td>

                                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                                {{ $sale->user?->name ?? 'Usuario eliminado' }}
                                            </td>

                                            <td class="px-5 py-3">
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                    <span class="h-2 w-2 rounded-full" style="background: {{ $color }}"></span>
                                                    {{ $methodName }}
                                                </span>
                                            </td>

                                            <td class="px-5 py-3 text-center text-gray-700 dark:text-gray-300">
                                                {{ number_format($sale->items->sum('quantity')) }}
                                            </td>

                                            <td class="px-5 py-3 text-right font-bold tabular-nums text-gray-900 dark:text-white">
                                                {{ $currency($sale->total) }}
                                            </td>

                                            <td class="px-5 py-3 text-center print:hidden">
                                                <a
                                                    href="{{ route('sales.show', $sale) }}"
                                                    title="Ver venta"
                                                    aria-label="Ver venta {{ $sale->folio }}"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-teal-700 transition hover:bg-teal-100 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-teal-900/30 dark:text-teal-300 dark:hover:bg-teal-900/60"
                                                >
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
                                                        <circle cx="12" cy="12" r="3" stroke-width="2"/>
                                                    </svg>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- SIN RESULTADOS --}}
                        <div x-show="q && matches === 0" x-cloak class="px-5 py-12 text-center">
                            <p class="font-semibold text-gray-700 dark:text-gray-200">
                                Ninguna venta de esta página coincide
                            </p>
                            <p class="mt-1 text-xs text-gray-400">
                                Prueba con otro folio o nombre.
                            </p>
                        </div>

                        {{-- PAGINACIÓN --}}
                        @if ($sales->hasPages())
                            <div class="border-t border-gray-100 p-4 dark:border-gray-700 print:hidden">
                                {{ $sales->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </section>


{{-- VENDEDORES + PRODUCTOS --}}
<section class="grid grid-cols-1 gap-5 xl:grid-cols-3">

    {{-- RENDIMIENTO POR VENDEDOR --}}
    <article
        x-data="{ expanded: false }"
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
        {{-- ENCABEZADO DESPLEGABLE --}}
        <button
            type="button"
            @click="expanded = !expanded"
            :aria-expanded="expanded"
            aria-controls="sellers-content"
            class="flex w-full items-center justify-between gap-3 p-4 text-left transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-500 dark:hover:bg-gray-700/30 sm:p-5"
        >
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H2v-2a4 4 0 017.75-1.3M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a3 3 0 11-6 0 3 3 0 016 0zM9 10a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                </div>

                <div class="min-w-0">
                    <h2 class="font-bold text-gray-900 dark:text-white">
                        Rendimiento por vendedor
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $salesBySeller->count() }}
                        {{ $salesBySeller->count() === 1 ? 'vendedor' : 'vendedores' }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span
                    class="hidden text-xs font-medium text-teal-600 dark:text-teal-400 sm:inline"
                    x-text="expanded ? 'Ocultar lista' : 'Mostrar lista'"
                ></span>

                <svg
                    class="h-5 w-5 text-gray-500 transition-transform duration-300 dark:text-gray-400"
                    :class="{ 'rotate-180': expanded }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        d="m6 9 6 6 6-6"
                    />
                </svg>
            </div>
        </button>

        {{-- LISTA DESPLEGABLE --}}
        <div
            id="sellers-content"
            x-show="expanded"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="border-t border-gray-100 p-4 dark:border-gray-700 sm:p-5"
        >
            <ol class="space-y-5">
                @forelse ($salesBySeller as $i => $seller)
                    @php
                        $pct = ((float) $seller->amount / $maxSeller) * 100;
                        $share = $totalAmount > 0
                            ? round((float) $seller->amount / $totalAmount * 100)
                            : 0;
                    @endphp

                    <li class="flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-500 text-base font-bold text-white">
                            {{ mb_strtoupper(mb_substr($seller->seller ?: '?', 0, 1)) }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm font-semibold text-gray-800 dark:text-white">
                                    {{ $seller->seller }}

                                    @if ($i === 0 && $salesBySeller->count() > 1)
                                        <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                            Top
                                        </span>
                                    @endif
                                </p>

                                <p class="shrink-0 text-sm font-bold tabular-nums text-gray-900 dark:text-white">
                                    {{ $currency($seller->amount) }}
                                </p>
                            </div>

                            <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-teal-500 transition-all duration-700"
                                    style="width: {{ max(2, $pct) }}%"
                                ></div>
                            </div>

                            <p class="mt-1 text-xs text-gray-400">
                                {{ $seller->sales_count }}
                                {{ $seller->sales_count == 1 ? 'venta' : 'ventas' }}
                                · {{ $share }}% del total
                            </p>
                        </div>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-gray-400">
                        No hay ventas registradas en el periodo.
                    </li>
                @endforelse
            </ol>
        </div>
    </article>

    {{-- PRODUCTOS MÁS VENDIDOS --}}
    <article
        x-data="{ expanded: false }"
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 xl:col-span-2"
    >
        {{-- ENCABEZADO DESPLEGABLE --}}
        <button
            type="button"
            @click="expanded = !expanded"
            :aria-expanded="expanded"
            aria-controls="products-content"
            class="flex w-full items-center justify-between gap-3 p-4 text-left transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-500 dark:hover:bg-gray-700/30 sm:p-5"
        >
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3v18h18M7 14l4-4 4 4 6-7"
                        />
                    </svg>
                </div>

                <div class="min-w-0">
                    <h2 class="font-bold text-gray-900 dark:text-white">
                        10 Productos más vendidos
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $topProducts->count() }} productos en el periodo
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span
                    class="hidden text-xs font-medium text-teal-600 dark:text-teal-400 sm:inline"
                    x-text="expanded ? 'Ocultar lista' : 'Mostrar lista'"
                ></span>

                <svg
                    class="h-5 w-5 text-gray-500 transition-transform duration-300 dark:text-gray-400"
                    :class="{ 'rotate-180': expanded }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        d="m6 9 6 6 6-6"
                    />
                </svg>
            </div>
        </button>

        {{-- LISTAS DESPLEGABLES --}}
        <div
            id="products-content"
            x-show="expanded"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="border-t border-gray-100 dark:border-gray-700"
        >
            {{-- TARJETAS MÓVILES --}}
            <ul class="divide-y divide-gray-100 dark:divide-gray-700 md:hidden">
                @forelse ($topProducts as $i => $product)
                    <li class="p-4">
                        <div class="flex items-start gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ $i + 1 }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="font-semibold leading-snug text-gray-900 dark:text-white">
                                    {{ $product->product_name }}
                                </p>

                                <p class="text-xs text-gray-400">
                                    {{ $product->product_sku }}
                                </p>

                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div
                                        class="h-full rounded-full bg-teal-500"
                                        style="width: {{ max(3, ((int) $product->units / $maxUnits) * 100) }}%"
                                    ></div>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-bold tabular-nums text-gray-900 dark:text-white">
                                        {{ $currency($product->amount) }}
                                    </span>

                                    @if ($product->modified_price_items > 0)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                            Precio modificado · {{ $product->modified_price_items }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-sm text-gray-400">
                        No hay productos vendidos en este periodo.
                    </li>
                @endforelse
            </ul>

            {{-- TABLA ESCRITORIO --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900/60 dark:text-gray-400">
                        <tr>
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">Producto</th>
                            <th class="px-5 py-3 text-right">Unidades</th>
                            <th class="px-5 py-3 text-right">Importe</th>
                            <th class="px-5 py-3 text-center">Precio</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($topProducts as $i => $product)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-5 py-3 text-gray-400">
                                    {{ $i + 1 }}
                                </td>

                                <td class="px-5 py-3">
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ $product->product_name }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ $product->product_sku }}
                                    </p>
                                </td>

                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <div class="hidden h-2 w-20 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700 lg:block">
                                            <div
                                                class="h-full rounded-full bg-teal-500"
                                                style="width: {{ max(3, ((int) $product->units / $maxUnits) * 100) }}%"
                                            ></div>
                                        </div>

                                        <span class="rounded-lg bg-teal-50 px-3 py-1 font-bold tabular-nums text-teal-700 dark:bg-teal-900/30 dark:text-teal-300">
                                            {{ number_format($product->units) }}
                                        </span>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                                    {{ $currency($product->amount) }}
                                </td>

                                <td class="px-5 py-3 text-center">
                                    @if ($product->modified_price_items > 0)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                            Modificado · {{ $product->modified_price_items }}
                                        </span>
                                    @else
                                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                            Normal
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-gray-400">
                                    No hay productos vendidos en este periodo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="border-t border-gray-100 px-5 py-3 text-xs text-gray-400 dark:border-gray-700">
                «Precio modificado» indica que se cobró un precio distinto al de catálogo registrado al momento de la venta.
            </p>
        </div>
    </article>
</section>


        </div>
    </div>

   <style>
    @keyframes report-slide {
        0% {
            transform: translateX(-100%);
        }

        100% {
            transform: translateX(300%);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .transition,
        .transition-all {
            transition: none !important;
        }
    }
</style>
    </style>
</x-app-layout>
