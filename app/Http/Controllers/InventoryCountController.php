<?php

namespace App\Http\Controllers;

use App\Models\InventoryCount;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryCountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isJefe = $user->isJefe();

        // Solo el jefe puede ver la lista de archivados
        $showArchived = $isJefe && $request->boolean('archivados');

        $counts = InventoryCount::with('user')
            ->withCount([
                'items',
                'items as counted_items_count' => fn ($q) => $q->whereNotNull('quantity_counted'),
            ])
            // El jefe ve todos; los demás solo los que ellos hicieron
            ->when(! $isJefe, fn ($q) => $q->where('user_id', $user->id))
            ->when(
                $showArchived,
                fn ($q) => $q->whereNotNull('archived_at'),
                fn ($q) => $q->whereNull('archived_at')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $archivedTotal = $isJefe
            ? InventoryCount::whereNotNull('archived_at')->count()
            : 0;

        return view('inventory-counts.index', compact(
            'counts',
            'isJefe',
            'showArchived',
            'archivedTotal'
        ));
    }

    public function create()
    {
        return view('inventory-counts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'count_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $count = DB::transaction(function () use ($validated, $request) {
            $lastId = InventoryCount::max('id') ?? 0;

            $count = InventoryCount::create([
                'folio' => 'INV-' . str_pad(
                    $lastId + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),
                'user_id' => $request->user()->id,
                'count_date' => $validated['count_date'],
                'description' => $validated['description'] ?? null,
                'status' => 'en_proceso',
            ]);

            // Guardar una fotografía del stock esperado de cada producto activo
            Product::where('is_active', true)
                ->orderBy('id')
                ->chunk(200, function ($products) use ($count) {
                    foreach ($products as $product) {
                        $count->items()->create([
                            'product_id' => $product->id,
                            'stock_expected' => $product->stock,
                        ]);
                    }
                });

            return $count;
        }, 3);

        return redirect()
            ->route('inventory-counts.count', $count)
            ->with('success', "Inventario {$count->folio} creado correctamente. Puedes iniciar el conteo.");
    }

    public function show(Request $request, InventoryCount $inventoryCount)
    {
        $this->ensureVisible($request, $inventoryCount);

        $inventoryCount->load('user', 'items.product.images');
        return view('inventory-counts.show', compact('inventoryCount'));
    }

    public function updateItem(Request $request, InventoryCount $inventoryCount, $item)
    {
        $this->ensureVisible($request, $inventoryCount);

        // Lógica para actualizar un item individual si lo usas vía AJAX
        $validated = $request->validate([
            'quantity_counted' => ['required', 'integer', 'min:0']
        ]);

        $inventoryItem = $inventoryCount->items()->findOrFail($item);
        $inventoryItem->update([
            'quantity_counted' => $validated['quantity_counted'],
            'counted_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function count(Request $request, InventoryCount $inventoryCount)
    {
        $this->ensureVisible($request, $inventoryCount);

        if ($inventoryCount->status !== 'en_proceso') {
            return redirect()
                ->route('inventory-counts.show', $inventoryCount)
                ->withErrors(['count' => 'Este inventario ya no está en proceso.']);
        }

        $inventoryCount->load('user', 'items.product.images');

        $initialItems = $inventoryCount->items
            ->whereNotNull('quantity_counted')
            ->map(fn ($item) => $this->scannerPayload($item->product, $item->stock_expected)
                + ['quantity' => (int) $item->quantity_counted])
            ->values()
            ->all();

        return view('inventory-counts.count', compact('inventoryCount', 'initialItems'));
    }

    public function lookup(Request $request, InventoryCount $inventoryCount)
    {
        $this->ensureVisible($request, $inventoryCount);

        $sku = strtoupper(trim((string) $request->query('sku')));

        $item = $inventoryCount->items()
            ->whereHas('product', fn ($q) => $q->where('sku', $sku))
            ->with('product.images')
            ->first();

        if (! $item) {
            return response()->json(
                ['message' => 'Ese producto no pertenece a este inventario.'],
                404
            );
        }

        return response()->json($this->scannerPayload($item->product, $item->stock_expected));
    }

    public function storeCount(Request $request, InventoryCount $inventoryCount)
    {
        $this->ensureVisible($request, $inventoryCount);

        abort_unless($inventoryCount->status === 'en_proceso', 403);

        $data = $request->validate([
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'integer', 'distinct'],
            'items.*.quantity'     => ['required', 'integer', 'min:0', 'max:2147483647'],
        ]);

        DB::transaction(function () use ($inventoryCount, $data, $request) {
            $rows = collect($data['items'])->keyBy('product_id');

            $inventoryCount->items()
                ->whereIn('product_id', $rows->keys())
                ->get()
                ->each(function ($item) use ($rows) {
                    $qty = (int) $rows[$item->product_id]['quantity'];

                    $item->update([
                        'quantity_counted' => $qty,
                        'counted_at'       => now(),
                    ]);
                });
        });

        return redirect()
            ->route('inventory-counts.show', $inventoryCount)
            ->with('success', 'Conteo guardado.');
    }

    public function updateItemAjax(Request $request, InventoryCount $inventoryCount)
    {
        $this->ensureVisible($request, $inventoryCount);

        if ($inventoryCount->status !== 'en_proceso') {
            return response()->json(['message' => 'El inventario ya no está en proceso.'], 403);
        }

        $validated = $request->validate([
            'product_id'       => ['required', 'integer', 'exists:inventory_count_items,product_id'],
            'quantity_counted' => ['required', 'integer', 'min:0'],
        ]);

        // Buscamos el item específico de este inventario físico
        $item = $inventoryCount->items()
            ->where('product_id', $validated['product_id'])
            ->firstOrFail();

        // Calculamos la diferencia automáticamente (Físico vs Esperado en sistema)
        $quantityCounted = (int) $validated['quantity_counted'];
        $difference = $quantityCounted - $item->stock_expected;

        $item->update([
            'quantity_counted' => $quantityCounted,
            'difference'       => $difference,
            'counted_at'       => now(),
        ]);

        return response()->json([
            'success'    => true,
            'difference' => $difference,
        ]);
    }

    public function saveAllItems(Request $request, InventoryCount $inventoryCount)
    {
        $this->ensureVisible($request, $inventoryCount);

        if ($inventoryCount->status !== 'en_proceso') {
            return back()->withErrors([
                'inventory' => 'Este inventario ya no está abierto para modificaciones.',
            ]);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
        ]);

        // Solo se guardan los productos que sí se capturaron.
        // Los que vienen vacíos se quedan sin contar (no se convierten en cero).
        $quantities = collect($validated['items'])
            ->filter(fn ($quantity) => $quantity !== null && $quantity !== '');

        if ($quantities->isEmpty()) {
            return back()->withErrors([
                'items' => 'Captura la cantidad de al menos un producto.',
            ]);
        }

        DB::transaction(function () use ($inventoryCount, $quantities, $request) {
            $count = InventoryCount::whereKey($inventoryCount->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($count->status !== 'en_proceso') {
                throw ValidationException::withMessages([
                    'inventory' => 'Este inventario ya no está abierto para modificaciones.',
                ]);
            }

            $items = $count->items()
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $itemId => $quantity) {
                $item = $items->get((int) $itemId);

                if (! $item) {
                    throw ValidationException::withMessages([
                        'items' => 'Uno de los productos no pertenece a este inventario.',
                    ]);
                }

                $item->update([
                    'quantity_counted' => (int) $quantity,
                    'difference' => (int) $quantity - $item->stock_expected,
                    'counted_by' => $request->user()->id,
                    'counted_at' => now(),
                ]);
            }
        }, 3);

        return redirect()
            ->route('inventory-counts.index', $inventoryCount);
    }

    /**
     * Aplica las diferencias del conteo al stock real (solo jefe).
     */
    public function apply(Request $request, InventoryCount $inventoryCount)
    {
        abort_unless($request->user()->isJefe(), 403);

        $adjusted = 0;
        $skipped = 0;

        DB::transaction(function () use ($inventoryCount, $request, &$adjusted, &$skipped) {
            $count = InventoryCount::whereKey($inventoryCount->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($count->applied_at !== null) {
                throw ValidationException::withMessages([
                    'apply' => 'Este inventario ya fue aplicado.',
                ]);
            }

            if ($count->archived_at !== null) {
                throw ValidationException::withMessages([
                    'apply' => 'Restaura el inventario antes de aplicarlo.',
                ]);
            }

            $items = $count->items()
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get();

            // Solo cuentan los productos que realmente se contaron.
            // Los que no se contaron conservan su stock actual.
            $counted = $items->filter(fn ($item) => $item->quantity_counted !== null);
            $skipped = $items->count() - $counted->count();

            if ($counted->isEmpty()) {
                throw ValidationException::withMessages([
                    'apply' => 'Todavía no hay productos contados en este inventario.',
                ]);
            }

            $products = Product::whereIn('id', $counted->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($counted as $item) {
                $difference = (int) $item->quantity_counted - (int) $item->stock_expected;

                if ($difference === 0 || ! $products->has($item->product_id)) {
                    continue;
                }

                // Se suma la diferencia al stock actual para no perder
                // ventas o movimientos hechos después de crear el conteo.
                $product = $products[$item->product_id];
                $before = (int) $product->stock;
                $after = max(0, $before + $difference);

                if ($after === $before) {
                    continue;
                }

                $product->stock = $after;
                $product->save();
                $adjusted++;

                // Deja el ajuste registrado en el historial de movimientos
                (new InventoryMovement())->forceFill([
                    'product_id'     => $product->id,
                    'user_id'        => $request->user()->id,
                    'type'           => 'ajuste',
                    'quantity'       => abs($after - $before),
                    'stock_before'   => $before,
                    'stock_after'    => $after,
                    'reason'         => "Ajuste por inventario físico {$count->folio}",
                    'reference_type' => 'inventory_count',
                    'reference_id'   => $count->id,
                ])->save();
            }

            $count->forceFill([
                'status'     => 'aplicado',
                'applied_at' => now(),
                'applied_by' => $request->user()->id,
                'closed_at'  => $count->closed_at ?? now(),
            ])->save();
        }, 3);

        $message = "Inventario {$inventoryCount->folio} aplicado: se ajustó el stock de {$adjusted} producto(s).";

        if ($skipped > 0) {
            $message .= " {$skipped} sin contar conservaron su stock.";
        }

        return redirect()
            ->route('inventory-counts.index')
            ->with('success', $message);
    }

    /**
     * Archiva un inventario: deja de verse para los inventaristas (solo jefe).
     */
    public function archive(Request $request, InventoryCount $inventoryCount)
    {
        abort_unless($request->user()->isJefe(), 403);

        $inventoryCount->forceFill([
            'archived_at' => now(),
            'archived_by' => $request->user()->id,
        ])->save();

        return back()->with('success', "Inventario {$inventoryCount->folio} archivado.");
    }

    /**
     * Saca un inventario del archivo (solo jefe).
     */
    public function restore(Request $request, InventoryCount $inventoryCount)
    {
        abort_unless($request->user()->isJefe(), 403);

        $inventoryCount->forceFill([
            'archived_at' => null,
            'archived_by' => null,
        ])->save();

        return back()->with('success', "Inventario {$inventoryCount->folio} restaurado.");
    }

    /**
     * El jefe accede a todo; los demás solo a sus propios conteos no archivados.
     */
    private function ensureVisible(Request $request, InventoryCount $inventoryCount): void
    {
        $user = $request->user();

        if ($user->isJefe()) {
            return;
        }

        abort_unless(
            $inventoryCount->user_id === $user->id && $inventoryCount->archived_at === null,
            404
        );
    }

    private function scannerPayload($product, int $stock): array
    {
        $image = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        return [
            'id'    => $product->id,
            'name'  => $product->name,
            'sku'   => $product->sku,
            'stock' => $stock,
            'image' => $image
                ? asset('storage/' . ($image->thumb_path ?? $image->path))
                : null,
        ];
    }
}
