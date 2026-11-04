<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    /**
     * Mostrar el punto de venta.
     *
     * Se envía todo el catálogo activo porque el escaneo y la búsqueda
     * se resuelven en el navegador (si limitas la lista, un producto
     * fuera del límite no se podría escanear).
     */
    public function index()
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with('images')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $image = $product->images->firstWhere('is_primary', true)
                    ?? $product->images->first();

                $imagePath = $image?->thumb_path ?: $image?->path;

                if ($imagePath && Str::startsWith($imagePath, ['http://', 'https://'])) {
                    $imageUrl = $imagePath;
                } elseif ($imagePath && Str::startsWith($imagePath, 'storage/')) {
                    $imageUrl = asset($imagePath);
                } elseif ($imagePath) {
                    $imageUrl = Storage::url($imagePath);
                } else {
                    $imageUrl = null;
                }

                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'stock' => (int) $product->stock,
                    'image' => $imageUrl,
                ];
            });

        return view('sales.index', compact('products'));
    }

    /**
     * Registrar una venta y actualizar el inventario.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999999'],

            // Precio de la nota: puede ser distinto al del catálogo.
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],

            'payment_method' => ['required', 'in:efectivo,tarjeta,transferencia'],

            // Opcional: solo sirve para calcular el cambio en efectivo.
            'amount_received' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],

            // Foto del comprobante (solo tarjeta / transferencia).
            'receipt' => ['nullable', 'image', 'max:5120'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $sale = DB::transaction(function () use ($validated, $request) {
            $requestedItems = collect($validated['items'])
                ->sortBy('product_id')
                ->values();

            $products = Product::query()
                ->whereIn('id', $requestedItems->pluck('product_id')->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $saleLines = [];

            foreach ($requestedItems as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];

                if (!$product || !$product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => 'Uno de los productos ya no está disponible.',
                    ]);
                }

                if ($quantity > $product->stock) {
                    throw ValidationException::withMessages([
                        'items' => "No hay existencias suficientes de {$product->name}. Stock disponible: {$product->stock}.",
                    ]);
                }

                // Precio de catálogo (siempre desde la base de datos).
                $originalPrice = round((float) $product->price, 2);

                // Precio de la nota: el que escribió el vendedor, o el de catálogo.
                $unitPrice = isset($item['unit_price']) && $item['unit_price'] !== ''
                    ? round((float) $item['unit_price'], 2)
                    : $originalPrice;

                $lineSubtotal = round($unitPrice * $quantity, 2);
                $subtotal = round($subtotal + $lineSubtotal, 2);

                $saleLines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'original_price' => $originalPrice,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $total = round($subtotal, 2);
            $paymentMethod = $validated['payment_method'];

            if ($paymentMethod === 'efectivo') {
                $received = $validated['amount_received'] ?? null;

                if ($received === null || $received === '') {
                    // No capturó con cuánto pagan: se asume pago exacto.
                    $amountReceived = $total;
                } else {
                    $amountReceived = round((float) $received, 2);

                    if ($amountReceived < $total) {
                        throw ValidationException::withMessages([
                            'amount_received' => 'El efectivo recibido no puede ser menor que el total.',
                        ]);
                    }
                }

                $change = round($amountReceived - $total, 2);
            } else {
                // Tarjeta o transferencia: se registra el total como pagado.
                $amountReceived = $total;
                $change = 0;
            }

            $sale = Sale::create([
                'folio' => 'V-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(5)),
                'user_id' => $request->user()->id,
                'subtotal' => $subtotal,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'amount_received' => $amountReceived,
                'change_amount' => $change,
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($saleLines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $quantity = $line['quantity'];

                $stockBefore = (int) $product->stock;
                $stockAfter = $stockBefore - $quantity;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $line['unit_price'],
                    'original_price' => $line['original_price'],
                    'subtotal' => $line['subtotal'],
                ]);

                // El precio del catálogo NO se modifica.
                $product->stock = $stockAfter;
                $product->save();

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'salida',
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reason' => 'Venta ' . $sale->folio,
                ]);
            }

            return $sale;
        }, 3);

        // El comprobante se guarda ya con la venta confirmada.
        if ($sale->payment_method !== 'efectivo' && $request->hasFile('receipt')) {
            $sale->update([
                'receipt_path' => $request->file('receipt')->store('receipts', 'public'),
            ]);
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', '¡Venta registrada correctamente!');
    }

    /**
     * Historial de ventas.
     * Vendedor: solo las que él realizó. Jefe: todas.
     */

/**
 * Historial de ventas.
 * Vendedor: solo sus ventas.
 * Jefe: todas las ventas y posibilidad de filtrar por vendedor.
 * Los resultados y el resumen corresponden únicamente al día seleccionado.
 */
public function history(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:50'],
            'date' => ['nullable', 'date'],
            'seller' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $user = $request->user();
        $isBoss = $this->isBoss($user);

        // Si no se selecciona fecha, mostrar el día actual.
        $selectedDate = $request->input('date') ?: now()->toDateString();

        $base = Sale::query()
            // Vendedor: únicamente sus propias ventas.
            ->when(!$isBoss, fn ($q) => $q->where('user_id', $user->id))

            // Jefe: puede filtrar por vendedor.
            ->when(
                $isBoss && $request->filled('seller'),
                fn ($q) => $q->where('user_id', $request->integer('seller'))
            )

            // Solo ventas del día seleccionado.
            ->whereDate('created_at', $selectedDate)

            // Buscar cualquier fragmento del folio, incluidos los últimos dígitos.
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(
                    'folio',
                    'like',
                    '%' . trim($request->input('search')) . '%'
                )
            );

        // Resumen exclusivo del día seleccionado.
        $summary = [
            'count' => (clone $base)->count(),
            'total' => (float) (clone $base)->sum('total'),
        ];

        $sales = (clone $base)
            ->with('user')
            ->withCount('items')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $sellers = $isBoss
            ? User::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('sales.history', compact(
            'sales',
            'summary',
            'sellers',
            'isBoss',
            'selectedDate'
        ));
    }


    /**
     * Mostrar el ticket de una venta.
     * El vendedor solo puede ver las suyas; el jefe, todas.
     */
    public function show(Request $request, Sale $sale)
    {
        abort_unless(
            $this->isBoss($request->user()) || $sale->user_id === $request->user()->id,
            403
        );

        $sale->load([
            'items',
            'user',
        ]);

        $isBoss = $this->isBoss($request->user());

        return view('sales.show', compact('sale', 'isBoss'));
    }

    /**
     * Formulario para modificar una venta (solo jefe).
     */
    public function edit(Request $request, Sale $sale)
    {
        $this->authorizeBoss($request);

        $sale->load('items');

        $stocks = Product::query()
            ->whereIn('id', $sale->items->pluck('product_id')->filter()->all())
            ->pluck('stock', 'id');

        return view('sales.edit', compact('sale', 'stocks'));
    }

    /**
     * Guardar cambios de una venta (solo jefe).
     * Si cambia una cantidad, el inventario se ajusta por la diferencia.
     */
    public function update(Request $request, Sale $sale)
    {
        $this->authorizeBoss($request);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'payment_method' => ['required', 'in:efectivo,tarjeta,transferencia'],
            'amount_received' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated, $request, $sale) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            $saleItems = $sale->items()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $submitted = collect($validated['items'])->keyBy('id');

            if (
                $submitted->count() !== $saleItems->count()
                || $submitted->keys()->diff($saleItems->keys())->isNotEmpty()
            ) {
                throw ValidationException::withMessages([
                    'items' => 'Los productos de la venta no coinciden. Recarga la página e inténtalo de nuevo.',
                ]);
            }

            $products = Product::query()
                ->whereIn('id', $saleItems->pluck('product_id')->filter()->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;

            foreach ($saleItems as $saleItem) {
                $row = $submitted->get($saleItem->id);

                $newQty = (int) $row['quantity'];
                $oldQty = (int) $saleItem->quantity;
                $diff = $newQty - $oldQty;

                $unitPrice = round((float) $row['unit_price'], 2);
                $lineSubtotal = round($unitPrice * $newQty, 2);
                $subtotal = round($subtotal + $lineSubtotal, 2);

                if ($diff !== 0) {
                    $product = $products->get($saleItem->product_id);

                    if (!$product) {
                        throw ValidationException::withMessages([
                            'items' => "El producto {$saleItem->product_name} ya no existe, no se puede cambiar su cantidad.",
                        ]);
                    }

                    $stockBefore = (int) $product->stock;
                    $stockAfter = $stockBefore - $diff;

                    if ($stockAfter < 0) {
                        throw ValidationException::withMessages([
                            'items' => "No hay existencias suficientes de {$saleItem->product_name}. Stock disponible: {$stockBefore}.",
                        ]);
                    }

                    $product->stock = $stockAfter;
                    $product->save();

                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'user_id' => $request->user()->id,
                        'type' => $diff > 0 ? 'salida' : 'entrada',
                        'quantity' => abs($diff),
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reason' => 'Ajuste de venta ' . $sale->folio,
                    ]);
                }

                $saleItem->update([
                    'quantity' => $newQty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ]);
            }

            $total = round($subtotal, 2);
            $paymentMethod = $validated['payment_method'];

            if ($paymentMethod === 'efectivo') {
                $received = $validated['amount_received'] ?? null;

                if ($received === null || $received === '') {
                    $amountReceived = $total;
                } else {
                    $amountReceived = round((float) $received, 2);

                    if ($amountReceived < $total) {
                        throw ValidationException::withMessages([
                            'amount_received' => 'El efectivo recibido no puede ser menor que el total.',
                        ]);
                    }
                }

                $change = round($amountReceived - $total, 2);
            } else {
                $amountReceived = $total;
                $change = 0;
            }

            $sale->update([
                'subtotal' => $subtotal,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'amount_received' => $amountReceived,
                'change_amount' => $change,
                'notes' => $validated['notes'] ?? null,
            ]);
        }, 3);

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Venta actualizada correctamente.');
    }

    /**
     * Eliminar una venta (solo jefe): regresa el inventario y borra la venta.
     */
    public function destroy(Request $request, Sale $sale)
    {
        $this->authorizeBoss($request);

        $folio = $sale->folio;
        $receipt = $sale->receipt_path;

        DB::transaction(function () use ($request, $sale) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            $items = $sale->items()->orderBy('id')->lockForUpdate()->get();

            $products = Product::query()
                ->whereIn('id', $items->pluck('product_id')->filter()->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);

                if (!$product) {
                    continue;
                }

                $stockBefore = (int) $product->stock;
                $stockAfter = $stockBefore + (int) $item->quantity;

                $product->stock = $stockAfter;
                $product->save();

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'entrada',
                    'quantity' => (int) $item->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reason' => 'Venta ' . $sale->folio . ' eliminada',
                ]);
            }

            $sale->items()->delete();
            $sale->delete();
        }, 3);

        if ($receipt) {
            Storage::disk('public')->delete($receipt);
        }

        return redirect()
            ->route('sales.history')
            ->with('success', "La venta {$folio} se eliminó y se regresaron las existencias al inventario.");
    }

private function isBoss($user): bool
{
    return $user !== null && (int) $user->role_id === 1;
}
    /**
     * Autorizar acciones exclusivas del jefe.
     */
    private function authorizeBoss(Request $request): void
    {
        abort_unless(
            $this->isBoss($request->user()),
            403,
            'Solo el jefe puede hacer esto.'
        );
    }
}
