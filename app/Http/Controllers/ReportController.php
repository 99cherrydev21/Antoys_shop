<?php
namespace App\Http\Controllers;


use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryCount;

class ReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REPORTE DE VENTAS
    |--------------------------------------------------------------------------
    */

    public function sales(Request $request)
    {
        $filters = $this->salesFilters($request);

        $query = $this->salesQuery($filters);

        // Indicadores generales
        $totalSales = (clone $query)->count();
        $totalAmount = (clone $query)->sum('total');
        $averageTicket = $totalSales > 0
            ? $totalAmount / $totalSales
            : 0;

        $unitsSold = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id');

        $this->applySalesFiltersToJoin($unitsSold, $filters);

        $unitsSold = $unitsSold->sum('sale_items.quantity');

        // Ventas por día
        $salesByDayQuery = (clone $query)
            ->selectRaw('DATE(sales.created_at) as date')
            ->selectRaw('COUNT(*) as sales_count')
            ->selectRaw('SUM(sales.total) as amount')
            ->groupByRaw('DATE(sales.created_at)')
            ->orderBy('date');

        $salesByDay = $salesByDayQuery->get();

        // Ventas por método de pago
        $salesByPayment = (clone $query)
            ->select('payment_method')
            ->selectRaw('COUNT(*) as sales_count')
            ->selectRaw('SUM(total) as amount')
            ->groupBy('payment_method')
            ->orderByDesc('amount')
            ->get();

        // Ventas por vendedor
        $salesBySeller = (clone $query)
            ->leftJoin('users', 'users.id', '=', 'sales.user_id')
            ->selectRaw("COALESCE(users.name, 'Usuario eliminado') as seller")
            ->selectRaw('COUNT(sales.id) as sales_count')
            ->selectRaw('SUM(sales.total) as amount')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('amount')
            ->get();

        // Los 10 productos más vendidos
        $topProductsQuery = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->select(
                'sale_items.product_id',
                'sale_items.product_name',
                'sale_items.product_sku'
            )
            ->selectRaw('SUM(sale_items.quantity) as units')
            ->selectRaw('SUM(sale_items.subtotal) as amount')
            ->selectRaw('SUM(CASE WHEN sale_items.unit_price <> sale_items.original_price THEN 1 ELSE 0 END) as modified_price_items')
            ->groupBy(
                'sale_items.product_id',
                'sale_items.product_name',
                'sale_items.product_sku'
            );

        $this->applySalesFiltersToJoin($topProductsQuery, $filters);

        $topProducts = $topProductsQuery
            ->orderByDesc('units')
            ->limit(10)
            ->get();

        // Ventas individuales
        $sales = (clone $query)
            ->with(['user', 'items'])
            ->latest('sales.created_at')
            ->paginate(15)
            ->withQueryString();

        // Vendedores disponibles para el filtro
        $sellers = User::query()
            ->whereHas('sales')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reports.sales', compact(
            'filters',
            'totalSales',
            'totalAmount',
            'averageTicket',
            'unitsSold',
            'salesByDay',
            'salesByPayment',
            'salesBySeller',
            'topProducts',
            'sales',
            'sellers'
        ));
    }

    public function salesExport(Request $request)
    {
        $filters = $this->salesFilters($request);

        $sales = $this->salesQuery($filters)
            ->with(['user', 'items'])
            ->latest('sales.created_at')
            ->get();

        return $this->csvDownload(
            'reporte-ventas-' . now()->format('Y-m-d') . '.csv',
            ['Fecha', 'Folio', 'Vendedor', 'Método de pago', 'Estado', 'Unidades', 'Subtotal', 'Total'],
            $sales->map(function ($sale) {
                return [
                    $sale->created_at->format('Y-m-d H:i:s'),
                    $sale->folio,
                    $sale->user?->name ?? 'Usuario eliminado',
                    $sale->payment_method,
                    $sale->status,
                    $sale->items->sum('quantity'),
                    $sale->subtotal,
                    $sale->total,
                ];
            })->all()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REPORTE DE INVENTARIO
    |--------------------------------------------------------------------------
    */

   public function inventory(Request $request)
{
    $filters = $request->validate([
        'search' => ['nullable', 'string', 'max:150'],
        'status' => ['nullable', 'in:all,out,low,normal'],
        'threshold' => ['nullable', 'integer', 'min:0', 'max:999999'],
        'count_id' => ['nullable', 'integer', 'exists:inventory_counts,id'],
    ]);

    $filters['search'] = trim($filters['search'] ?? '');
    $filters['status'] = $filters['status'] ?? 'all';
    $filters['threshold'] = (int) ($filters['threshold'] ?? 5);

    // =========================================================
    // INVENTARIO ACTUAL
    // =========================================================

    $activeProducts = Product::query()
        ->where('is_active', true);

    $totalProducts = (clone $activeProducts)->count();
    $totalStock = (clone $activeProducts)->sum('stock');

    $inventoryValue = (clone $activeProducts)
        ->selectRaw('COALESCE(SUM(stock * price), 0) as value')
        ->value('value');

    $outOfStock = (clone $activeProducts)
        ->where('stock', '<=', 0)
        ->count();

    $lowStock = (clone $activeProducts)
        ->where('stock', '>', 0)
        ->where('stock', '<=', $filters['threshold'])
        ->count();

    // =========================================================
    // PRODUCTOS DEL INVENTARIO ACTUAL
    // =========================================================

    $productsQuery = Product::query()
        ->where('is_active', true);

    if ($filters['search'] !== '') {
        $search = $filters['search'];

        $productsQuery->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");
        });
    }

    if ($filters['status'] === 'out') {
        $productsQuery->where('stock', '<=', 0);
    } elseif ($filters['status'] === 'low') {
        $productsQuery->where('stock', '>', 0)
            ->where('stock', '<=', $filters['threshold']);
    } elseif ($filters['status'] === 'normal') {
        $productsQuery->where('stock', '>', $filters['threshold']);
    }

    $products = $productsQuery
        ->orderBy('name')
        ->paginate(15)
        ->withQueryString();

    // =========================================================
    // HISTORIAL DE CONTEOS APLICADOS
    // =========================================================

    // Solo mostramos conteos que realmente tienen fecha de aplicación.
    $inventoryCounts = InventoryCount::query()
        ->whereNotNull('applied_at')
        ->orderByDesc('applied_at')
        ->get([
            'id',
            'folio',
            'count_date',
            'applied_at',
            'applied_by',
        ]);

    // Conteo seleccionado por el usuario.
    $selectedCount = null;

    if (!empty($filters['count_id'])) {
        $selectedCount = $inventoryCounts->firstWhere(
            'id',
            (int) $filters['count_id']
        );
    }

    // Si no se seleccionó un folio válido, elegimos el más reciente.
    $selectedCount ??= $inventoryCounts->first();

    $historicalItems = collect();

    $historicalSummary = [
        'totalProducts' => 0,
        'countedProducts' => 0,
        'expectedUnits' => 0,
        'countedUnits' => 0,
        'difference' => 0,
        'adjustedUnits' => 0,
    ];

    if ($selectedCount) {
        /*
         * Buscamos el último movimiento asociado a cada producto
         * dentro del conteo seleccionado.
         *
         * Esto evita duplicar productos si accidentalmente existe
         * más de un movimiento asociado al mismo conteo y producto.
         */
        $latestMovementIds = DB::table('inventory_movements')
            ->select('product_id')
            ->selectRaw('MAX(id) as movement_id')
            ->where('reference_type', 'inventory_count')
            ->where('reference_id', $selectedCount->id)
            ->groupBy('product_id');

        $historicalItems = DB::table('inventory_count_items as items')
            ->join('products', 'products.id', '=', 'items.product_id')
            ->leftJoinSub(
                $latestMovementIds,
                'latest_movements',
                function ($join) {
                    $join->on(
                        'latest_movements.product_id',
                        '=',
                        'items.product_id'
                    );
                }
            )
            ->leftJoin(
                'inventory_movements as movements',
                'movements.id',
                '=',
                'latest_movements.movement_id'
            )
            ->where('items.inventory_count_id', $selectedCount->id)
            ->select([
                'products.id as product_id',
                'products.name',
                'products.sku',
                'products.stock as current_stock',
                'products.price',
                'items.stock_expected',
                'items.quantity_counted',
                'movements.quantity as adjusted_units',
                'movements.stock_before',
                'movements.stock_after',
            ])
            ->selectRaw(
                'COALESCE(items.difference, items.quantity_counted - items.stock_expected) as difference'
            )
            ->orderBy('products.name')
            ->get();

        $historicalSummary = [
            'totalProducts' => $historicalItems->count(),

            'countedProducts' => $historicalItems
                ->whereNotNull('quantity_counted')
                ->count(),

            'expectedUnits' => (int) $historicalItems
                ->sum('stock_expected'),

            'countedUnits' => (int) $historicalItems
                ->sum(fn ($item) => $item->quantity_counted ?? 0),

            'difference' => (int) $historicalItems
                ->sum(fn ($item) => $item->difference ?? 0),

            'adjustedUnits' => (int) $historicalItems
                ->sum(fn ($item) => $item->adjusted_units ?? 0),
        ];
    }

    return view('reports.inventory', compact(
        'filters',
        'totalProducts',
        'totalStock',
        'inventoryValue',
        'outOfStock',
        'lowStock',
        'products',
        'inventoryCounts',
        'selectedCount',
        'historicalItems',
        'historicalSummary'
    ));
}

    public function inventoryExport(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:all,out,low,normal'],
            'threshold' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $filters['search'] = trim($filters['search'] ?? '');
        $filters['status'] = $filters['status'] ?? 'all';
        $filters['threshold'] = (int) ($filters['threshold'] ?? 5);

        $query = Product::query()
            ->where('is_active', true);

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($filters['status'] === 'out') {
            $query->where('stock', '<=', 0);
        } elseif ($filters['status'] === 'low') {
            $query->where('stock', '>', 0)
                ->where('stock', '<=', $filters['threshold']);
        } elseif ($filters['status'] === 'normal') {
            $query->where('stock', '>', $filters['threshold']);
        }

        $products = $query->orderBy('name')->get();

        return $this->csvDownload(
            'reporte-inventario-' . now()->format('Y-m-d') . '.csv',
            ['SKU', 'Producto', 'Existencias', 'Precio unitario', 'Valor del inventario', 'Estado'],
            $products->map(function ($product) use ($filters) {
                $status = $product->stock <= 0
                    ? 'Agotado'
                    : ($product->stock <= $filters['threshold'] ? 'Stock bajo' : 'Normal');

                return [
                    $product->sku,
                    $product->name,
                    $product->stock,
                    $product->price,
                    $product->stock * $product->price,
                    $status,
                ];
            })->all()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REPORTE DE MOVIMIENTOS
    |--------------------------------------------------------------------------
    */

    public function movements(Request $request)
    {
        $filters = $this->movementFilters($request);

        $query = $this->movementsQuery($filters);

        $totalMovements = (clone $query)->count();

        $unitsIn = (clone $query)
            ->where('type', 'entrada')
            ->sum('quantity');

        $unitsOut = (clone $query)
            ->where('type', 'salida')
            ->sum('quantity');

        $movements = $query
            ->with(['product', 'user'])
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('reports.movements', compact(
            'filters',
            'totalMovements',
            'unitsIn',
            'unitsOut',
            'movements'
        ));
    }

    public function movementsExport(Request $request)
    {
        $filters = $this->movementFilters($request);

        $movements = $this->movementsQuery($filters)
            ->with(['product', 'user'])
            ->latest('created_at')
            ->get();

        return $this->csvDownload(
            'reporte-movimientos-' . now()->format('Y-m-d') . '.csv',
            [
                'Fecha',
                'Producto',
                'SKU',
                'Tipo',
                'Cantidad',
                'Stock anterior',
                'Stock posterior',
                'Usuario',
                'Motivo',
                'Referencia',
            ],
            $movements->map(function ($movement) {
                return [
                    $movement->created_at->format('Y-m-d H:i:s'),
                    $movement->product?->name ?? 'Producto eliminado',
                    $movement->product?->sku ?? '',
                    $movement->type,
                    $movement->quantity,
                    $movement->stock_before,
                    $movement->stock_after,
                    $movement->user?->name ?? 'Usuario eliminado',
                    $movement->reason,
                    $movement->reference_type && $movement->reference_id
                        ? $movement->reference_type . '#' . $movement->reference_id
                        : '',
                ];
            })->all()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONSULTAS Y FILTROS COMPARTIDOS
    |--------------------------------------------------------------------------
    */

    private function salesFilters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'seller_id' => ['nullable', 'integer', 'exists:users,id'],
            'payment_method' => ['nullable', 'in:efectivo,tarjeta,transferencia'],
        ]);

        return [
            'from' => $validated['from'] ?? now()->startOfMonth()->toDateString(),
            'to' => $validated['to'] ?? now()->toDateString(),
            'seller_id' => $validated['seller_id'] ?? '',
            'payment_method' => $validated['payment_method'] ?? '',
        ];
    }

    private function salesQuery(array $filters): Builder
    {
        $query = Sale::query()
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [
                Carbon::parse($filters['from'])->startOfDay(),
                Carbon::parse($filters['to'])->endOfDay(),
            ]);

        if ($filters['seller_id'] !== '') {
            $query->where('sales.user_id', $filters['seller_id']);
        }

        if ($filters['payment_method'] !== '') {
            $query->where('sales.payment_method', $filters['payment_method']);
        }

        return $query;
    }

    private function applySalesFiltersToJoin($query, array $filters): void
    {
        $query->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [
                Carbon::parse($filters['from'])->startOfDay(),
                Carbon::parse($filters['to'])->endOfDay(),
            ]);

        if ($filters['seller_id'] !== '') {
            $query->where('sales.user_id', $filters['seller_id']);
        }

        if ($filters['payment_method'] !== '') {
            $query->where('sales.payment_method', $filters['payment_method']);
        }
    }

    private function movementFilters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', 'in:entrada,salida'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        return [
            'from' => $validated['from'] ?? now()->subDays(29)->toDateString(),
            'to' => $validated['to'] ?? now()->toDateString(),
            'type' => $validated['type'] ?? '',
            'search' => trim($validated['search'] ?? ''),
        ];
    }

    private function movementsQuery(array $filters): Builder
    {
        $query = InventoryMovement::query()
            ->whereBetween('created_at', [
                Carbon::parse($filters['from'])->startOfDay(),
                Carbon::parse($filters['to'])->endOfDay(),
            ]);

        if ($filters['type'] !== '') {
            $query->where('type', $filters['type']);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->whereHas('product', function ($productQuery) use ($search) {
                $productQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORTACIÓN CSV
    |--------------------------------------------------------------------------
    */

    private function csvDownload(string $filename, array $headers, array $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // BOM para que Excel reconozca correctamente los acentos.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers, ',', '"', '\\');

            foreach ($rows as $row) {
                fputcsv($handle, $row, ',', '"', '\\');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
