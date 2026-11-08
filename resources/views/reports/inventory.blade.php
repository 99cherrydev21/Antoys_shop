@php
    // ── Filtros activos ────────────────────────────────────────────
    $search = (string) ($filters['search'] ?? '');
    $status = $filters['status'] ?? 'all';
    $threshold = (int) ($filters['threshold'] ?? 5);
    $selectedId = $selectedCount?->id;

    $hasFilters = $search !== '' || $status !== 'all';

    // Parámetros que se conservan al cambiar un solo filtro (incluye el conteo elegido)
    $params = fn(array $extra = []) => array_filter(
        array_merge(
            ['search' => $search, 'status' => $status, 'threshold' => $threshold, 'count_id' => $selectedId],
            $extra,
        ),
        fn($v) => $v !== null && $v !== '' && $v !== 'all',
    );

    // ── Indicadores generales ──────────────────────────────────────
    $totalProducts = (int) $totalProducts;
    $totalStock = (int) $totalStock;
    $inventoryValue = (float) $inventoryValue;
    $outOfStock = (int) $outOfStock;
    $lowStock = (int) $lowStock;
    $normalStock = max(0, $totalProducts - $outOfStock - $lowStock);

    $statusMeta = [
        'out' => [
            'Agotado',
            'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300',
            'bg-red-500',
            'text-red-600 dark:text-red-400',
        ],
        'low' => [
            'Stock bajo',
            'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
            'bg-amber-400',
            'text-amber-600 dark:text-amber-400',
        ],
        'normal' => [
            'Normal',
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
            'bg-emerald-500',
            'text-gray-900 dark:text-white',
        ],
    ];
    $statusOf = fn(int $stock) => $stock <= 0 ? 'out' : ($stock <= $threshold ? 'low' : 'normal');

    $maxStock = max(1, (int) $products->getCollection()->max('stock'));

    $chip = fn(
        bool $active,
    ) => 'inline-flex min-h-[44px] shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-teal-500 ' .
        ($active
            ? 'border-teal-500 bg-teal-50 text-teal-700 dark:bg-teal-900/40 dark:text-teal-200'
            : 'border-gray-200 bg-white text-gray-600 hover:border-teal-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300');

    $statusChips = [
        'all' => ['Todos', $totalProducts, null],
        'out' => ['Agotados', $outOfStock, '#ef4444'],
        'low' => ['Stock bajo', $lowStock, '#fbbf24'],
        'normal' => ['Normal', $normalStock, '#10b981'],
    ];

    // ── Conteo seleccionado: filas para la auditoría ───────────────
    $rows = collect();

    if ($selectedCount) {
        $rows = collect($historicalItems ?? [])
            ->map(function ($item) use ($threshold) {
                $counted = $item->quantity_counted !== null ? (int) $item->quantity_counted : null;
                $diff = $item->difference !== null ? (int) $item->difference : null;
                $adjusted = $item->adjusted_units !== null ? (int) $item->adjusted_units : null;
                $after = $item->stock_after !== null ? (int) $item->stock_after : null;
                $current = (int) $item->current_stock;
                $price = data_get($item, 'price');

                return [
                    'name' => (string) $item->name,
                    'sku' => (string) $item->sku,
                    'expected' => (int) $item->stock_expected,
                    'counted' => $counted,
                    'diff' => $diff,
                    'adjusted' => $adjusted,
                    'after' => $after,
                    'current' => $current,
                    // Cuánto cambió el stock desde que se aplicó el conteo
                    'since' => $after !== null ? $current - $after : null,
                    'status' => $current <= 0 ? 'out' : ($current <= $threshold ? 'low' : 'normal'),
                    'value' => $price !== null && $diff !== null ? round($diff * (float) $price, 2) : null,
                    'search' => mb_strtolower($item->name . ' ' . $item->sku),
                ];
            })
            ->values();
    }

    $auditTotal = $rows->count();
    $auditCounted = $rows->filter(fn($r) => $r['counted'] !== null)->count();
    $auditUncounted = $auditTotal - $auditCounted;
    $matched = $rows->filter(fn($r) => $r['diff'] === 0)->count();
    $shortRows = $rows->filter(fn($r) => $r['diff'] !== null && $r['diff'] < 0);
    $overRows = $rows->filter(fn($r) => $r['diff'] !== null && $r['diff'] > 0);
    $shortUnits = abs((int) $shortRows->sum('diff'));
    $overUnits = (int) $overRows->sum('diff');
    $movedUnits = (int) $rows->sum(fn($r) => abs((int) ($r['adjusted'] ?? 0)));
    $accuracy = $auditCounted > 0 ? (int) round(($matched / $auditCounted) * 100) : null;
    $accColor =
        $accuracy === null ? '#9ca3af' : ($accuracy >= 95 ? '#10b981' : ($accuracy >= 80 ? '#f59e0b' : '#ef4444'));
    $hasValue = $rows->contains(fn($r) => $r['value'] !== null);
    $valueDiff = (float) $rows->sum(fn($r) => $r['value'] ?? 0);
    $perfect = $auditCounted > 0 && $shortRows->isEmpty() && $overRows->isEmpty();

    $countsTotal = $inventoryCounts->count();
    $quickCounts = $inventoryCounts->take(6);
@endphp

<x-app-layout>
    <div class="relative min-h-screen bg-gray-100 transition-colors duration-300 dark:bg-gray-900" x-data="{ loading: false }"
        @click="if ($event.target.closest('a[data-load]')) loading = true" @pageshow.window="loading = false"
        @keydown.window.slash="if (!['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); document.getElementById('search')?.focus(); }">
        <x-background-glow />

        {{-- Barra de carga --}}
        <div x-show="loading" x-cloak class="fixed inset-x-0 top-0 z-50 h-1 overflow-hidden bg-teal-100 print:hidden"
            role="progressbar" aria-label="Cargando reporte">
            <div class="h-full w-1/3 bg-teal-500" style="animation: report-slide 1s ease-in-out infinite"></div>
        </div>

        <div class="relative mx-auto max-w-7xl space-y-5 px-4 py-5 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">

            {{-- ENCABEZADO --}}
            <header class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="mt-0.5 text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">Reportes de Inventario</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Conteos aplicados y existencias · {{ now()->format('d/m/Y H:i') }}
                    </p>
                </div>

                <div class="flex shrink-0 gap-2 print:hidden">

                    <a href="{{ route('reports.inventory.export', request()->query()) }}" aria-label="Exportar CSV"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-teal-600 px-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900 sm:px-4">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="M12 3v12m0 0l4-4m-4 4l-4-4M4 17v4h16v-4" />
                        </svg>
                        <span class="hidden sm:inline">Exportar CSV</span>
                    </a>
                </div>
            </header>


 {{-- PESTAÑAS DE REPORTES --}}
            <nav class="-mx-4 overflow-x-auto px-4 print:hidden sm:mx-0 sm:px-0" aria-label="Tipo de reporte">
                <div class="inline-flex rounded-2xl border border-gray-200 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-800">

                    <a href="{{ route('reports.inventory') }}" aria-current="page"
                       class="rounded-xl bg-gradient-to-r from-blue-500 to-teal-500 px-4 py-2.5 text-sm font-semibold text-white shadow">
                        Inventario
                    </a>
                    <a href="{{ route('reports.movements') }}" data-load
                       class="rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:text-teal-600 dark:text-gray-300 dark:hover:text-teal-300">
                        Movimientos
                    </a>
                </div>
            </nav>



            {{-- =========================================================
     HISTORIAL DE INVENTARIOS APLICADOS
========================================================= --}}


            @php
                $countsTotal = $inventoryCounts->count();
                $productsTotal = $historicalItems->count();
            @endphp

            <section x-data="{ expanded: true }"
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-colors dark:border-gray-700 dark:bg-gray-800"
                aria-labelledby="history-title">
                {{-- ENCABEZADO --}}
                <div
                    class="relative overflow-hidden border-b border-gray-100 bg-gradient-to-r from-teal-50 via-white to-white dark:border-gray-700 dark:from-teal-950/30 dark:via-gray-800 dark:to-gray-800">
                    <div class="absolute -right-10 -top-12 h-40 w-40 rounded-full bg-teal-500/5 blur-2xl"></div>

                    <div class="relative flex items-center justify-between gap-3 p-4 sm:p-5">
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 3v18h18M8 16V9m4 7V5m4 11v-4m4 4V7" />
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 id="history-title"
                                        class="text-base font-extrabold text-gray-900 dark:text-white sm:text-lg">
                                        Historial de inventarios
                                    </h2>

                                </div>

                            </div>
                        </div>

                        {{-- BOTÓN PARA OCULTAR / MOSTRAR --}}
                        <button type="button" @click="expanded = !expanded" :aria-expanded="expanded"
                            aria-controls="inventory-history-content"
                            class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600 shadow-sm transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-teal-700 dark:hover:bg-teal-950/40 dark:hover:text-teal-300">
                            <span class="hidden sm:inline" x-text="expanded ? 'Ocultar' : 'Mostrar'"></span>

                            <svg class="h-5 w-5 transition-transform duration-300" :class="expanded ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    d="m19 9-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- CONTENIDO PLEGABLE --}}
                <div id="inventory-history-content" x-show="expanded"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2">
                    @if ($countsTotal > 0)

                        {{-- SELECTOR DE FECHA --}}
                        <div class="border-b border-gray-100 p-4 dark:border-gray-700 sm:p-5">
                            <form method="GET" action="{{ route('reports.inventory') }}"
                                class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                <input type="hidden" name="search" value="{{ $search }}">
                                <input type="hidden" name="status" value="{{ $status }}">
                                <input type="hidden" name="threshold" value="{{ $threshold }}">

                                <div class="min-w-0 flex-1">
                                    <label for="count_id"
                                        class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        <svg class="h-4 w-4 text-teal-600 dark:text-teal-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                                d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                                        </svg>
                                        Consultar por fecha
                                    </label>

                                    <select id="count_id" name="count_id" onchange="this.form.requestSubmit()"
                                        class="h-12 w-full rounded-xl border-gray-200 bg-gray-50 px-4 text-sm font-semibold text-gray-800 shadow-sm transition focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:bg-gray-800">
                                        @foreach ($inventoryCounts as $count)
                                            <option value="{{ $count->id }}" @selected($selectedCount?->id === $count->id)>
                                                {{ $count->count_date?->format('d/m/Y') ?? \Carbon\Carbon::parse($count->count_date)->format('d/m/Y') }}
                                                — {{ $count->folio }}
                                                (Aplicado: {{ $count->applied_at?->format('d/m/Y H:i') ?? 'Sin aplicar' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>


                            </form>

                        </div>

                        @if ($selectedCount)

                            {{-- COMPARACIÓN --}}
                            <div class="border-t border-gray-100 dark:border-gray-700">
                                <div
                                    class="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                                    <div>
                                        <h3 class="font-extrabold text-gray-900 dark:text-white">
                                            Comparación por producto
                                        </h3>
                                    </div>
                                </div>

                                @if ($historicalItems->isNotEmpty())

                                    {{-- MÓVIL --}}
                                    <ul class="divide-y divide-gray-100 dark:divide-gray-700 md:hidden">
                                        @foreach ($historicalItems as $item)
                                            @php
                                                $currentStock = (int) $item->current_stock;
                                                $difference = $item->difference;

                                                $currentStatus =
                                                    $currentStock <= 0
                                                        ? 'Agotado'
                                                        : ($currentStock <= $threshold
                                                            ? 'Stock bajo'
                                                            : 'Normal');

                                                $currentBadge =
                                                    $currentStock <= 0
                                                        ? 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300'
                                                        : ($currentStock <= $threshold
                                                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'
                                                            : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300');

                                                $differenceColor =
                                                    $difference === null
                                                        ? 'text-gray-400'
                                                        : ($difference < 0
                                                            ? 'text-red-600 dark:text-red-400'
                                                            : ($difference > 0
                                                                ? 'text-emerald-600 dark:text-emerald-400'
                                                                : 'text-gray-700 dark:text-gray-300'));
                                            @endphp

                                            <li class="p-4 transition hover:bg-gray-50/80 dark:hover:bg-gray-700/20">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <p class="truncate font-bold text-gray-900 dark:text-white">
                                                            {{ $item->name }}
                                                        </p>
                                                        <p class="mt-1 font-mono text-xs text-gray-400">
                                                            {{ $item->sku }}
                                                        </p>
                                                    </div>

                                                    <span
                                                        class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $currentBadge }}">
                                                        {{ $currentStatus }}
                                                    </span>
                                                </div>

                                                <div class="mt-4 grid grid-cols-2 gap-3">
                                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/60">
                                                        <p
                                                            class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                            Esperado
                                                        </p>
                                                        <p
                                                            class="mt-1 text-base font-bold text-gray-800 dark:text-gray-200">
                                                            {{ number_format($item->stock_expected) }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/60">
                                                        <p
                                                            class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                            Contado
                                                        </p>
                                                        <p
                                                            class="mt-1 text-base font-bold text-gray-800 dark:text-gray-200">
                                                            {{ $item->quantity_counted !== null ? number_format($item->quantity_counted) : '—' }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/60">
                                                        <p
                                                            class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                            Diferencia
                                                        </p>
                                                        <p
                                                            class="mt-1 text-base font-extrabold {{ $differenceColor }}">
                                                            @if ($difference === null)
                                                                —
                                                            @else
                                                                {{ $difference > 0 ? '+' : '' }}{{ number_format($difference) }}
                                                            @endif
                                                        </p>
                                                    </div>

                                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/60">
                                                        <p
                                                            class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                            Ajuste registrado
                                                        </p>
                                                        <p
                                                            class="mt-1 text-base font-bold text-gray-800 dark:text-gray-200">
                                                            {{ $item->adjusted_units !== null ? number_format($item->adjusted_units) : '—' }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div
                                                    class="mt-3 flex items-center justify-between gap-3 rounded-xl border border-teal-100 bg-teal-50/70 p-3 dark:border-teal-900/50 dark:bg-teal-950/20">
                                                    <div>
                                                        <p
                                                            class="text-[10px] font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400">
                                                            Stock actual
                                                        </p>
                                                        <p
                                                            class="mt-0.5 text-2xl font-black text-teal-700 dark:text-teal-300">
                                                            {{ number_format($currentStock) }}
                                                        </p>
                                                    </div>

                                                    <div class="text-right">
                                                        <p
                                                            class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                            Después del ajuste
                                                        </p>
                                                        <p
                                                            class="mt-1 text-lg font-bold text-gray-800 dark:text-gray-200">
                                                            {{ $item->stock_after !== null ? number_format($item->stock_after) : '—' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>

                                    {{-- ESCRITORIO --}}
                                    <div class="hidden overflow-x-auto md:block">
                                        <table class="w-full min-w-[850px] text-left text-sm">
                                            <thead
                                                class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500 dark:bg-gray-900/60 dark:text-gray-400">
                                                <tr>
                                                    <th class="px-5 py-4 font-bold">Producto</th>
                                                    <th class="px-4 py-4 text-right font-bold">Esperado</th>
                                                    <th class="px-4 py-4 text-right font-bold">Contado</th>
                                                    <th class="px-4 py-4 text-right font-bold">Diferencia</th>
                                                    <th class="px-4 py-4 text-right font-bold">Ajuste</th>
                                                    <th class="px-4 py-4 text-right font-bold">Stock actual</th>
                                                    <th class="px-5 py-4 text-center font-bold">Estado</th>
                                                </tr>
                                            </thead>

                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                @foreach ($historicalItems as $item)
                                                    @php
                                                        $currentStock = (int) $item->current_stock;
                                                        $difference = $item->difference;

                                                        $currentStatus =
                                                            $currentStock <= 0
                                                                ? 'Agotado'
                                                                : ($currentStock <= $threshold
                                                                    ? 'Stock bajo'
                                                                    : 'Normal');

                                                        $currentBadge =
                                                            $currentStock <= 0
                                                                ? 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300'
                                                                : ($currentStock <= $threshold
                                                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'
                                                                    : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300');

                                                        $differenceColor =
                                                            $difference === null
                                                                ? 'text-gray-400'
                                                                : ($difference < 0
                                                                    ? 'text-red-600 dark:text-red-400'
                                                                    : ($difference > 0
                                                                        ? 'text-emerald-600 dark:text-emerald-400'
                                                                        : 'text-gray-700 dark:text-gray-300'));
                                                    @endphp

                                                    <tr
                                                        class="transition hover:bg-teal-50/40 dark:hover:bg-teal-950/10">
                                                        <td class="px-5 py-4">
                                                            <p class="font-bold text-gray-900 dark:text-white">
                                                                {{ $item->name }}
                                                            </p>
                                                            <p class="mt-1 font-mono text-xs text-gray-400">
                                                                {{ $item->sku }}
                                                            </p>
                                                        </td>

                                                        <td
                                                            class="px-4 py-4 text-right tabular-nums text-gray-600 dark:text-gray-300">
                                                            {{ number_format($item->stock_expected) }}
                                                        </td>

                                                        <td
                                                            class="px-4 py-4 text-right tabular-nums text-gray-600 dark:text-gray-300">
                                                            {{ $item->quantity_counted !== null ? number_format($item->quantity_counted) : '—' }}
                                                        </td>

                                                        <td
                                                            class="px-4 py-4 text-right font-extrabold tabular-nums {{ $differenceColor }}">
                                                            @if ($difference === null)
                                                                —
                                                            @else
                                                                {{ $difference > 0 ? '+' : '' }}{{ number_format($difference) }}
                                                            @endif
                                                        </td>

                                                        <td
                                                            class="px-4 py-4 text-right tabular-nums text-gray-600 dark:text-gray-300">
                                                            {{ $item->adjusted_units !== null ? number_format($item->adjusted_units) : '—' }}
                                                        </td>

                                                        <td
                                                            class="px-4 py-4 text-right text-base font-extrabold tabular-nums text-teal-600 dark:text-teal-400">
                                                            {{ number_format($currentStock) }}
                                                        </td>

                                                        <td class="px-5 py-4 text-center">
                                                            <span
                                                                class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold {{ $currentBadge }}">
                                                                {{ $currentStatus }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="px-6 py-12 text-center">
                                        <div
                                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-width="1.8" stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l4.414 4.414A1 1 0 0 1 18 8.414V19a2 2 0 0 1-2 2Z" />
                                            </svg>
                                        </div>
                                        <p class="mt-3 font-bold text-gray-700 dark:text-gray-200">
                                            Sin productos registrados
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Este conteo no tiene productos para comparar.
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="px-6 py-12 text-center">
                                <div
                                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700">
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                                    </svg>
                                </div>

                                <h3 class="mt-4 font-bold text-gray-900 dark:text-white">
                                    No hay inventarios aplicados
                                </h3>

                                <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                                    Cuando se aplique un conteo, aparecerá aquí para que puedas consultar sus
                                    resultados.
                                </p>
                            </div>
                        @endif
                    @else
                        <div class="px-6 py-12 text-center">
                            <div
                                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                                </svg>
                            </div>

                            <h3 class="mt-4 font-bold text-gray-900 dark:text-white">
                                Todavía no hay inventarios aplicados
                            </h3>

                            <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                                Cuando el jefe aplique un conteo, aparecerá aquí para consultar su historial.
                            </p>
                        </div>
                    @endif
                </div>
            </section>


            {{-- ═══════════════ EXISTENCIAS ACTUALES ═══════════════ --}}

            {{-- FILTROS --}}
            <section id="existencias"
                class="scroll-mt-4 rounded-2xl border border-gray-200 bg-white/90 p-4 shadow-sm backdrop-blur dark:border-gray-700 dark:bg-gray-800/90 sm:p-5 print:hidden"
                x-data="{ adjust: false }">

                <div
                    class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($statusChips as $key => [$label, $count, $hex])
                        <a href="{{ route('reports.inventory', $params(['status' => $key])) }}#existencias" data-load
                            @if ($status === $key) aria-current="true" @endif
                            class="{{ $chip($status === $key) }}">
                            @if ($hex)
                                <span class="h-2.5 w-2.5 rounded-full"
                                    style="background: {{ $hex }}"></span>
                            @endif
                            {{ $label }}
                            <span
                                class="rounded-full bg-black/5 px-2 py-0.5 text-xs tabular-nums dark:bg-white/10">{{ number_format($count) }}</span>
                        </a>
                    @endforeach
                </div>

                {{-- Límite de stock bajo --}}
                <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                    <button type="button" @click="adjust = !adjust" :aria-expanded="adjust.toString()"
                        class="min-h-[44px] font-semibold text-teal-600 hover:text-teal-700 focus:outline-none focus:underline dark:text-teal-400">
                        <span x-text="adjust ? 'Cerrar' : 'Cambiar límite stock'"></span> {{ $threshold }}
                    </button>
                </div>

                <div x-show="adjust" x-cloak x-transition class="mt-1 rounded-xl bg-gray-50 p-4 dark:bg-gray-900/50">
                    <p class="mb-2 text-xs font-semibold text-gray-600 dark:text-gray-300">Opciones rápidas</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ([3, 5, 10, 20, 50] as $preset)
                            <a href="{{ route('reports.inventory', $params(['threshold' => $preset])) }}#existencias"
                                data-load class="{{ $chip($threshold === $preset) }}">{{ $preset }}</a>
                        @endforeach
                    </div>

                    <form method="GET" action="{{ route('reports.inventory') }}" @submit="loading = true"
                        class="mt-4 flex items-end gap-2">
                        @foreach ($params(['threshold' => null]) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach

                        <div class="flex-1 sm:max-w-[200px]">
                            <label for="threshold"
                                class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-300">Otro
                                valor</label>
                            <input id="threshold" name="threshold" type="number" min="0" max="999999"
                                inputmode="numeric" value="{{ $threshold }}"
                                class="h-11 w-full rounded-xl border-gray-300 bg-white text-base text-gray-900 focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        </div>
                        <button type="submit"
                            class="h-11 rounded-xl bg-teal-600 px-5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-400">
                            Aplicar
                        </button>
                    </form>
                </div>

                @if ($hasFilters)
                    <div class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                        <a href="{{ route('reports.inventory', array_filter(['threshold' => $threshold, 'count_id' => $selectedId])) }}#existencias"
                            data-load
                            class="inline-flex min-h-[44px] items-center gap-1 text-sm font-semibold text-gray-600 transition hover:text-red-600 dark:text-gray-300 dark:hover:text-red-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-width="2.5" stroke-linecap="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Quitar todos los filtros
                        </a>
                    </div>
                @endif
            </section>

            {{-- LISTADO --}}
            <section
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                aria-labelledby="list-title">
                <div
                    class="flex flex-col gap-1 border-b border-gray-100 p-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <div>
                        <h2 id="list-title" class="font-bold text-gray-900 dark:text-white">Existencias por producto
                        </h2>
                    </div>
                    <span class="text-xs text-gray-400">Página {{ $products->currentPage() }} de
                        {{ max(1, $products->lastPage()) }}</span>
                </div>

                @if ($products->count())

                    {{-- Tarjetas (móvil y tableta) --}}
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700 md:hidden">
                        @foreach ($products as $product)
                            @php
                                $stock = (int) $product->stock;
                                $key = $statusOf($stock);
                                [$statusLabel, $badge, $barColor, $stockColor] = $statusMeta[$key];
                                $barPct = $stock > 0 ? max(4, round(($stock / $maxStock) * 100)) : 0;
                            @endphp
                            <li class="p-4">
                                <div class="flex items-start gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold leading-snug text-gray-900 dark:text-white">
                                            {{ $product->name }}</p>

                                        <div class="mt-1 flex items-center gap-1">
                                            <span
                                                class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $product->sku }}</span>
                                            <button type="button" x-data="{ ok: false }"
                                                data-sku="{{ $product->sku }}"
                                                @click="copyText($el.dataset.sku).then(() => { ok = true; setTimeout(() => ok = false, 1500) })"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:hover:bg-gray-700"
                                                aria-label="Copiar SKU {{ $product->sku }}">
                                                <svg x-show="!ok" class="h-4 w-4" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                <svg x-show="ok" x-cloak class="h-4 w-4 text-emerald-500"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    aria-hidden="true">
                                                    <path stroke-width="2.5" stroke-linecap="round"
                                                        stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </div>

                                        <span
                                            class="mt-1.5 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">{{ $statusLabel }}</span>
                                    </div>

                                    <div class="shrink-0 text-right">
                                        <p
                                            class="text-3xl font-extrabold leading-none tabular-nums {{ $stockColor }}">
                                            {{ number_format($stock) }}</p>
                                        <p class="mt-1 text-xs text-gray-400">
                                            {{ $stock === 1 ? 'unidad' : 'unidades' }}</p>
                                    </div>
                                </div>

                                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div class="h-full rounded-full {{ $barColor }}"
                                        style="width: {{ $barPct }}%"></div>
                                </div>

                                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <dt class="text-xs text-gray-400">Precio</dt>
                                        <dd class="font-semibold tabular-nums text-gray-800 dark:text-gray-200">
                                            ${{ number_format((float) $product->price, 2) }}</dd>
                                    </div>
                                    <div class="text-right">
                                        <dt class="text-xs text-gray-400">Valor total</dt>
                                        <dd class="font-semibold tabular-nums text-gray-800 dark:text-gray-200">
                                            ${{ number_format($stock * (float) $product->price, 2) }}</dd>
                                    </div>
                                </dl>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Tabla (escritorio) --}}
                    <div class="hidden overflow-x-auto md:block">
                        <table class="w-full text-left text-sm">
                            <thead
                                class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:bg-gray-900/60 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-5 py-3 font-semibold">Producto</th>
                                    <th scope="col" class="px-5 py-3 text-right font-semibold">Existencias</th>
                                    <th scope="col" class="px-5 py-3 text-right font-semibold">Precio</th>
                                    <th scope="col" class="px-5 py-3 text-right font-semibold">Valor total</th>
                                    <th scope="col" class="px-5 py-3 text-center font-semibold">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($products as $product)
                                    @php
                                        $stock = (int) $product->stock;
                                        $key = $statusOf($stock);
                                        [$statusLabel, $badge, $barColor, $stockColor] = $statusMeta[$key];
                                        $barPct = $stock > 0 ? max(4, round(($stock / $maxStock) * 100)) : 0;
                                    @endphp
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-5 py-3">
                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                {{ $product->name }}</p>
                                            <div class="mt-0.5 flex items-center gap-1">
                                                <span
                                                    class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $product->sku }}</span>
                                                <button type="button" x-data="{ ok: false }"
                                                    data-sku="{{ $product->sku }}"
                                                    @click="copyText($el.dataset.sku).then(() => { ok = true; setTimeout(() => ok = false, 1500) })"
                                                    class="flex h-7 w-7 items-center justify-center rounded-md text-gray-300 transition hover:bg-gray-100 hover:text-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:hover:bg-gray-700 print:hidden"
                                                    aria-label="Copiar SKU {{ $product->sku }}" title="Copiar SKU">
                                                    <svg x-show="!ok" class="h-3.5 w-3.5" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    <svg x-show="ok" x-cloak class="h-3.5 w-3.5 text-emerald-500"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                        aria-hidden="true">
                                                        <path stroke-width="2.5" stroke-linecap="round"
                                                            stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>

                                        <td class="px-5 py-3">
                                            <div class="flex items-center justify-end gap-3">

                                                <span
                                                    class="min-w-[2.5rem] text-end text-base font-bold tabular-nums {{ $stockColor }}">{{ number_format($stock) }}</span>
                                            </div>
                                        </td>

                                        <td
                                            class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-gray-600 dark:text-gray-300">
                                            ${{ number_format((float) $product->price, 2) }}</td>
                                        <td
                                            class="whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                                            ${{ number_format($stock * (float) $product->price, 2) }}</td>
                                        <td class="px-5 py-3 text-center">
                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $badge }}">{{ $statusLabel }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-14 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700"
                            aria-hidden="true">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7">
                                <path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z" />
                                <path d="m3.5 7.5 8.5 4.3 8.5-4.3M12 12v9" />
                            </svg>
                        </div>
                        @if ($hasFilters)
                            <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">No encontramos productos
                            </h3>
                            <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">Ningún producto
                                coincide con la búsqueda o el estado elegido.</p>
                            <a href="{{ route('reports.inventory', array_filter(['threshold' => $threshold, 'count_id' => $selectedId])) }}#existencias"
                                data-load
                                class="mt-5 inline-flex min-h-[44px] items-center rounded-xl bg-teal-600 px-5 text-sm font-semibold text-white transition hover:bg-teal-700 print:hidden">
                                Quitar filtros
                            </a>
                        @else
                            <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Aún no hay productos
                                activos</h3>
                            <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">Cuando registres
                                productos aparecerán aquí con sus existencias.</p>
                        @endif
                    </div>
                @endif

                @if ($products->hasPages())
                    <div class="border-t border-gray-100 p-4 dark:border-gray-700 print:hidden">
                        {{ $products->links() }}
                    </div>
                @endif
            </section>
        </div>

        {{-- Volver arriba --}}
        <button type="button" x-data="{ show: false }" @scroll.window.throttle.100ms="show = window.scrollY > 700"
            x-show="show" x-cloak x-transition.opacity @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
            aria-label="Volver arriba"
            class="fixed bottom-[calc(1rem+env(safe-area-inset-bottom))] right-4 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-teal-600 text-white shadow-lg transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900 print:hidden">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
            </svg>
        </button>
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

        @media (prefers-reduced-motion: no-preference) {
            html {
                scroll-behavior: smooth;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .transition,
            .transition-all {
                transition: none !important;
            }
        }

        @media print {

            nav,
            header button,
            .print\:hidden {
                display: none !important;
            }

            body,
            .min-h-screen {
                background: white !important;
                color: black !important;
            }

            .shadow-sm {
                box-shadow: none !important;
            }

            section,
            article {
                break-inside: avoid;
            }

            table {
                font-size: 10px !important;
            }

            tr {
                break-inside: avoid;
            }

            /* En papel siempre se usan las tablas */
            .md\:hidden,
            .lg\:hidden {
                display: none !important;
            }

            .hidden.md\:block,
            .hidden.lg\:block {
                display: block !important;
            }

            .xl\:table-cell {
                display: table-cell !important;
            }
        }
    </style>

    <script>
        // Copia al portapapeles (con respaldo para conexiones sin HTTPS)
        window.copyText = async (text) => {
            try {
                await navigator.clipboard.writeText(text);
            } catch (e) {
                const area = document.createElement('textarea');
                area.value = text;
                area.style.position = 'fixed';
                area.style.opacity = '0';
                document.body.appendChild(area);
                area.select();
                document.execCommand('copy');
                area.remove();
            }
        };

        document.addEventListener('alpine:init', () => {
            const money = v => new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(Number(v) || 0);

            // Número que sube de 0 al valor final (respeta "reducir movimiento")
            Alpine.data('countUp', (target, mode = 'int') => ({
                text: '',
                init() {
                    const fmt = v => mode === 'money' ?
                        money(v) :
                        new Intl.NumberFormat('es-MX').format(Math.round(v));

                    this.text = fmt(target);

                    if (!target || window.matchMedia('(prefers-reduced-motion: reduce)').matches)
                return;

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

            // Comparación esperado / contado / ajuste del conteo seleccionado (todo en el navegador)
            Alpine.data('countAudit', (rows) => ({
                rows,
                filter: 'all',
                sort: 'diff',
                q: '',
                step: 30,
                limit: 30,
                counts: {
                    all: rows.length,
                    diff: rows.filter(r => r.diff !== null && r.diff !== 0).length,
                    short: rows.filter(r => r.diff !== null && r.diff < 0).length,
                    over: rows.filter(r => r.diff !== null && r.diff > 0).length,
                    ok: rows.filter(r => r.diff === 0).length,
                    none: rows.filter(r => r.counted === null).length,
                },
                nf: new Intl.NumberFormat('es-MX'),
                chips: [{
                        key: 'all',
                        label: 'Todos',
                        dot: null
                    },
                    {
                        key: 'diff',
                        label: 'Con diferencia',
                        dot: '#f59e0b'
                    },
                    {
                        key: 'short',
                        label: 'Faltantes',
                        dot: '#ef4444'
                    },
                    {
                        key: 'over',
                        label: 'Sobrantes',
                        dot: '#10b981'
                    },
                    {
                        key: 'ok',
                        label: 'Coinciden',
                        dot: '#9ca3af'
                    },
                    {
                        key: 'none',
                        label: 'Sin contar',
                        dot: null
                    },
                ],

                init() {
                    // Al cambiar filtros se vuelve a la primera tanda
                    ['q', 'filter', 'sort'].forEach(key => this.$watch(key, () => {
                        this.limit = this.step;
                    }));
                },

                get filtered() {
                    const term = this.q.trim().toLowerCase();

                    const list = this.rows.filter(r => {
                        if (term && !r.search.includes(term)) return false;

                        switch (this.filter) {
                            case 'diff':
                                return r.diff !== null && r.diff !== 0;
                            case 'short':
                                return r.diff !== null && r.diff < 0;
                            case 'over':
                                return r.diff !== null && r.diff > 0;
                            case 'ok':
                                return r.diff === 0;
                            case 'none':
                                return r.counted === null;
                            default:
                                return true;
                        }
                    });

                    const abs = r => (r.diff === null ? -1 : Math.abs(r.diff));

                    if (this.sort === 'name') {
                        return list.sort((a, b) => a.name.localeCompare(b.name, 'es'));
                    }
                    if (this.sort === 'short') {
                        return list.sort((a, b) => (a.diff ?? Infinity) - (b.diff ?? Infinity));
                    }
                    return list.sort((a, b) => abs(b) - abs(a));
                },

                get visible() {
                    return this.filtered.slice(0, this.limit);
                },

                get hiddenCount() {
                    return Math.max(0, this.filtered.length - this.limit);
                },

                reset() {
                    this.q = '';
                    this.filter = 'all';
                },

                signed(n) {
                    if (n === null) return '—';
                    return (n > 0 ? '+' : n < 0 ? '−' : '') + this.nf.format(Math.abs(n));
                },

                // Texto corto de la diferencia
                diffShort(r) {
                    if (r.counted === null) return 'Sin contar';
                    if (r.diff === 0) return '0';
                    return this.signed(r.diff);
                },

                diffColor(r) {
                    if (r.diff === null) return 'text-gray-400';
                    if (r.diff < 0) return 'text-red-600 dark:text-red-400';
                    if (r.diff > 0) return 'text-emerald-600 dark:text-emerald-400';
                    return 'text-gray-700 dark:text-gray-300';
                },

                diffPill(r) {
                    if (r.diff === null)
                    return 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-400';
                    if (r.diff < 0)
                    return 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300';
                    if (r.diff > 0)
                    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300';
                    return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
                },

                borderColor(r) {
                    if (r.diff === null) return 'border-l-gray-300 dark:border-l-gray-600';
                    if (r.diff < 0) return 'border-l-red-500';
                    if (r.diff > 0) return 'border-l-emerald-500';
                    return 'border-l-transparent';
                },

                statusLabel(r) {
                    return r.status === 'out' ? 'Agotado' : (r.status === 'low' ? 'Stock bajo' :
                        'Normal');
                },

                statusBadge(r) {
                    if (r.status === 'out')
                    return 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300';
                    if (r.status === 'low')
                    return 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300';
                    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300';
                },
            }));
        });
    </script>
</x-app-layout>
