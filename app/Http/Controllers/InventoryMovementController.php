<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryMovementController extends Controller
{
    public function index(Product $product)
    {
        $movements = $product->movements()
            ->with('user')
            ->paginate(15);

        return view('inventory.movements', compact(
            'product',
            'movements'
        ));
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'type' => [
                'required',
                Rule::in(['entrada', 'salida']),
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:4294967295',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        DB::transaction(function () use (
            $validated,
            $product,
            $request
        ) {
            // Bloqueamos el producto para evitar cambios simultáneos.
            $lockedProduct = Product::whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = (int) $lockedProduct->stock;
            $quantity = (int) $validated['quantity'];

            if ($validated['type'] === 'salida' && $quantity > $before) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'quantity' => 'No hay existencias suficientes para realizar esta salida.',
                ]);
            }

            $after = $validated['type'] === 'entrada'
                ? $before + $quantity
                : $before - $quantity;

            if ($after > 4294967295) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'quantity' => 'La cantidad final excede el máximo permitido.',
                ]);
            }

            $lockedProduct->stock = $after;
            $lockedProduct->save();

            InventoryMovement::create([
                'product_id' => $lockedProduct->id,
                'user_id' => $request->user()?->id,
                'type' => $validated['type'],
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'reason' => $validated['reason'] ?? null,
            ]);
        });

        return redirect()
            ->route('inventory.movements.index', $product)
            ->with('success', 'Movimiento de inventario registrado correctamente.');
    }
}
