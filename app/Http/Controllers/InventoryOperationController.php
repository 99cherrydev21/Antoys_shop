<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryOperationController extends Controller
{
    /**
     * Muestra la pantalla para escanear productos.
     */
    public function create()
    {
        return view('inventory.operations');
    }

    /**
     * Busca un producto por su SKU leído por el escáner.
     */
    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100'],
        ]);

        $sku = strtoupper(trim($validated['sku']));

        $product = Product::with('images')
            ->where('sku', $sku)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'No se encontró un producto activo con el código: ' . $sku,
            ], 404);
        }

        $primaryImage = $product->images->firstWhere('is_primary', true)
            ?? $product->images->first();

        return response()->json([
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'stock' => $product->stock,
            'price' => $product->price,
            'image' => $primaryImage
                ? asset('storage/' . $primaryImage->path)
                : null,
        ]);
    }

    /**
     * Registra todos los productos de la operación en una transacción.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:entrada,salida'],
            'reason' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                'exists:products,id',
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ], [
            'items.required' => 'Debes escanear al menos un producto.',
            'items.min' => 'Debes escanear al menos un producto.',
            'items.*.product_id.distinct' =>
                'Un producto aparece duplicado. Revisa la lista.',
            'items.*.quantity.min' =>
                'Todas las cantidades deben ser mayores que cero.',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $items = collect($validated['items'])
                ->keyBy(fn ($item) => (int) $item['product_id']);

            $products = Product::whereIn('id', $items->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Comprobar todos los productos antes de modificar existencias.
            foreach ($items as $productId => $item) {
                $product = $products->get($productId);

                if (!$product || !$product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => 'Uno de los productos ya no está disponible.',
                    ]);
                }

                if (
                    $validated['type'] === 'salida'
                    && $product->stock < $item['quantity']
                ) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente para {$product->name}. "
                            . "Existencias: {$product->stock}; "
                            . "solicitadas: {$item['quantity']}.",
                    ]);
                }
            }

            // Actualizar existencias y registrar cada línea del movimiento.
            foreach ($items as $productId => $item) {
                $product = $products->get($productId);
                $before = (int) $product->stock;
                $quantity = (int) $item['quantity'];

                $after = $validated['type'] === 'entrada'
                    ? $before + $quantity
                    : $before - $quantity;

                $product->update(['stock' => $after]);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()?->id,
                    'type' => $validated['type'],
                    'quantity' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reason' => $validated['reason'] ?? null,
                ]);
            }
        }, 3);

        return redirect()
            ->route('products.index')
            ->with('success', 'La operación de inventario se registró correctamente.');
    }
}
