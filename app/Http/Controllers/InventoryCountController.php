<?php

namespace App\Http\Controllers;

use App\Models\InventoryCount;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryCountController extends Controller
{
    public function index()
    {
        $counts = InventoryCount::with('user')->latest()->paginate(10);
        return view('inventory-counts.index', compact('counts'));
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

    public function show(InventoryCount $inventoryCount)
    {
        $inventoryCount->load('user', 'items.product.images');
        return view('inventory-counts.show', compact('inventoryCount'));
    }

    public function updateItem(Request $request, InventoryCount $inventoryCount, $item)
    {
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

    public function count(InventoryCount $inventoryCount)
    {
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
        abort_unless($inventoryCount->status === 'en_proceso', 403);

        $data = $request->validate([
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'integer', 'distinct'],
            'items.*.quantity'     => ['required', 'integer', 'min:0', 'max:2147483647'],
            'missing_as_zero'      => ['nullable', 'boolean'],
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

            if ($request->boolean('missing_as_zero')) {
                $inventoryCount->items()
                    ->whereNull('quantity_counted')
                    ->get()
                    ->each(fn ($item) => $item->update([
                        'quantity_counted' => 0,
                        'counted_at'       => now(),
                    ]));
            }
        });

        return redirect()
            ->route('inventory-counts.show', $inventoryCount)
            ->with('success', 'Conteo guardado.');
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
    public function updateItemAjax(Request $request, InventoryCount $inventoryCount)
{
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

    // Actualizamos con la fecha, cantidad y diferencia calculada
    $item->update([
        'quantity_counted' => $quantityCounted,
        'difference'       => $difference, // Asegúrate de tener esta columna en tu migración de inventory_count_items
        'counted_at'       => now(),
    ]);

    return response()->json([
        'success'    => true,
        'difference' => $difference,
    ]);
}
public function saveAllItems(Request $request, InventoryCount $inventoryCount)
{
    if ($inventoryCount->status !== 'en_proceso') {
        return back()->withErrors([
            'inventory' => 'Este inventario ya no está abierto para modificaciones.',
        ]);
    }

    $validated = $request->validate([
        'items' => ['required', 'array', 'min:1'],
        'items.*' => [
            'required',
            'integer',
            'min:0',
            'max:2147483647',
        ],
    ]);

    DB::transaction(function () use ($inventoryCount, $validated, $request) {
        $count = InventoryCount::whereKey($inventoryCount->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($count->status !== 'en_proceso') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'inventory' => 'Este inventario ya no está abierto para modificaciones.',
            ]);
        }

        $items = $count->items()
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $quantities = $validated['items'];

        // Verificar que se envió exactamente una cantidad por cada producto.
        if (
            count($quantities) !== $items->count()
            || $items->contains(
                fn ($item) => ! array_key_exists(
                    (string) $item->id,
                    $quantities
                )
            )
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'items' => 'Debes capturar una cantidad para cada producto.',
            ]);
        }

        foreach ($items as $item) {
            $quantity = (int) $quantities[$item->id];

            $item->update([
                'quantity_counted' => $quantity,
                'difference' => $quantity - $item->stock_expected,
                'counted_by' => $request->user()->id,
                'counted_at' => now(),
            ]);
        }
    }, 3);

    return redirect()
        ->route('inventory-counts.index', $inventoryCount);

}
}
