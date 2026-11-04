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
     * Punto de venta (crear una venta nueva).
     */
    public function index()
    {
        return view('sales.index', [
            'products' => $this->posProducts(),
            'sale' => null,
        ]);
    }

    /**
     * Registrar una venta y actualizar el inventario.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->saleRules());

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

            $payment = $this->resolvePayment(
                $validated['payment_method'],
                $validated['amount_received'] ?? null,
                round($subtotal, 2)
            );

            $sale = Sale::create([
                'folio' => 'V-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(5)),
                'user_id' => $request->user()->id,
                'subtotal' => $subtotal,
                'total' => round($subtotal, 2),
                'payment_method' => $validated['payment_method'],
                'amount_received' => $payment['received'],
                'change_amount' => $payment['change'],
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
            ->when(!$isBoss, fn ($q) => $q->where('user_id', $user->id))
            ->when(
                $isBoss && $request->filled('seller'),
                fn ($q) => $q->where('user_id', $request->integer('seller'))
            )
            ->whereDate('created_at', $selectedDate)
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where('folio', 'like', '%' . trim($request->input('search')) . '%')
            );

        $summary = [
            'count' => (clone $base)->count(),
            'total' => (float) (clone $base)->sum('total'),
        ];

        $sales = (clone $base)
            ->with('user')
            ->withCount('items')
            ->orderBy('id', 'desc')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $sellers = $isBoss
            ? User::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('sales.history', compact('sales', 'summary', 'sellers', 'isBoss', 'selectedDate'));
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

        $sale->load(['items', 'user']);

        $isBoss = $this->isBoss($request->user());

        return view('sales.show', compact('sale', 'isBoss'));
    }

    /**
     * Editar una venta (solo jefe): usa la MISMA pantalla del punto de venta,
     * ya cargada con los productos, precios y pago de la venta.
     */
    public function edit(Request $request, Sale $sale)
    {
        $this->authorizeBoss($request);

        $sale->load('items');

        return view('sales.index', [
            'products' => $this->posProducts($sale),
            'sale' => $sale,
        ]);
    }

    /**
     * Guardar la venta editada (solo jefe).
     *
     * Se compara lo que tenía la venta contra lo que llega:
     *  - producto agregado  -> sale del inventario
     *  - producto quitado   -> regresa al inventario
     *  - cantidad cambiada  -> se ajusta la diferencia
     */
    public function update(Request $request, Sale $sale)
    {
        $this->authorizeBoss($request);

        $validated = $request->validate($this->saleRules());

        DB::transaction(function () use ($validated, $request, $sale) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            // Renglones actuales de la venta, por producto (en una venta hay uno por producto).
            $oldItems = $sale->items()
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->filter(fn ($item) => $item->product_id)
                ->keyBy(fn ($item) => (int) $item->product_id);

            // Renglones que llegan del formulario, por producto.
            $requested = collect($validated['items'])
                ->keyBy(fn ($row) => (int) $row['product_id']);

            $productIds = $oldItems->keys()
                ->merge($requested->keys())
                ->unique()
                ->sort()
                ->values();

            $products = Product::query()
                ->whereIn('id', $productIds->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;

            foreach ($productIds as $productId) {
                $product = $products->get($productId);
                $oldItem = $oldItems->get($productId);
                $row = $requested->get($productId);

                $oldQty = $oldItem ? (int) $oldItem->quantity : 0;
                $newQty = $row ? (int) $row['quantity'] : 0;
                $diff = $newQty - $oldQty;

                if (!$product) {
                    if ($row) {
                        throw ValidationException::withMessages([
                            'items' => 'Uno de los productos ya no está disponible.',
                        ]);
                    }

                    // Producto que ya no existe y se quitó de la venta.
                    $oldItem?->delete();
                    continue;
                }

                if ($row && !$oldItem && !$product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => "El producto {$product->name} ya no está disponible.",
                    ]);
                }

                // Ajuste de inventario por la diferencia.
                if ($diff !== 0) {
                    $stockBefore = (int) $product->stock;
                    $stockAfter = $stockBefore - $diff;

                    if ($stockAfter < 0) {
                        throw ValidationException::withMessages([
                            'items' => "No hay existencias suficientes de {$product->name}. Stock disponible: {$stockBefore}.",
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

                // Producto quitado de la venta.
                if (!$row) {
                    $oldItem->delete();
                    continue;
                }

                // Precio de la nota: el enviado; si no llega, el que ya tenía o el de catálogo.
                $catalogPrice = round((float) $product->price, 2);

                if (isset($row['unit_price']) && $row['unit_price'] !== '') {
                    $unitPrice = round((float) $row['unit_price'], 2);
                } elseif ($oldItem) {
                    $unitPrice = round((float) $oldItem->unit_price, 2);
                } else {
                    $unitPrice = $catalogPrice;
                }

                $lineSubtotal = round($unitPrice * $newQty, 2);
                $subtotal = round($subtotal + $lineSubtotal, 2);

                if ($oldItem) {
                    $oldItem->update([
                        'quantity' => $newQty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $lineSubtotal,
                    ]);
                } else {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'quantity' => $newQty,
                        'unit_price' => $unitPrice,
                        'original_price' => $catalogPrice,
                        'subtotal' => $lineSubtotal,
                    ]);
                }
            }

            $total = round($subtotal, 2);

            $payment = $this->resolvePayment(
                $validated['payment_method'],
                $validated['amount_received'] ?? null,
                $total
            );

            $sale->update([
                'subtotal' => $subtotal,
                'total' => $total,
                'payment_method' => $validated['payment_method'],
                'amount_received' => $payment['received'],
                'change_amount' => $payment['change'],
                'notes' => $validated['notes'] ?? null,
            ]);
        }, 3);

        // Si subieron una foto nueva, reemplaza el comprobante anterior.
        $sale->refresh();

        if ($sale->payment_method !== 'efectivo' && $request->hasFile('receipt')) {
            if ($sale->receipt_path) {
                Storage::disk('public')->delete($sale->receipt_path);
            }

            $sale->update([
                'receipt_path' => $request->file('receipt')->store('receipts', 'public'),
            ]);
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Venta actualizada y se ajustó el inventario.');
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

    // ─────────────────────────────────────────────────────────────
    // Ayudas internas
    // ─────────────────────────────────────────────────────────────

    /**
     * Reglas de validación compartidas por crear y editar.
     */
    private function saleRules(): array
    {
        return [
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
        ];
    }

    /**
     * Calcula efectivo recibido y cambio según el método de pago.
     */
    private function resolvePayment(string $method, $received, float $total): array
    {
        if ($method !== 'efectivo') {
            // Tarjeta o transferencia: se registra el total como pagado.
            return ['received' => $total, 'change' => 0];
        }

        if ($received === null || $received === '') {
            // No capturaron con cuánto pagan: se asume pago exacto.
            return ['received' => $total, 'change' => 0];
        }

        $received = round((float) $received, 2);

        if ($received < $total) {
            throw ValidationException::withMessages([
                'amount_received' => 'El efectivo recibido no puede ser menor que el total.',
            ]);
        }

        return ['received' => $received, 'change' => round($received - $total, 2)];
    }

    /**
     * Productos para el punto de venta.
     *
     * Se envía todo el catálogo activo porque el escaneo y la búsqueda
     * se resuelven en el navegador. Al editar una venta, las existencias
     * de sus productos incluyen las unidades que esa venta ya tiene
     * (son las que se pueden "devolver" o conservar), y se incluyen aunque
     * el producto se haya desactivado después.
     */
    private function posProducts(?Sale $sale = null)
    {
        $inSale = $sale
            ? $sale->items
                ->filter(fn ($item) => $item->product_id)
                ->groupBy('product_id')
                ->map(fn ($group) => (int) $group->sum('quantity'))
            : collect();

        return Product::query()
            ->where(function ($query) use ($inSale) {
                $query->where('is_active', true);

                if ($inSale->isNotEmpty()) {
                    $query->orWhereIn('id', $inSale->keys()->all());
                }
            })
            ->with('images')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($inSale) {
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
                    'stock' => (int) $product->stock + (int) ($inSale[$product->id] ?? 0),
                    'image' => $imageUrl,
                ];
            });
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
