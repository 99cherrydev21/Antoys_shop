<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Milon\Barcode\Facades\DNS2DFacade;
use App\Services\SkuGenerator;
use Illuminate\Support\Facades\DB;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;


class ProductController extends Controller
{


public function index(Request $request)
{
    $search = trim($request->input('search', ''));

    $products = Product::query()
    ->with('images')
        ->where('is_active', true)
        ->when($search !== '', function ($query) use ($search) {
            if (ctype_digit($search)) {
                $sku = 'ANT-' . str_pad($search, 7, '0', STR_PAD_LEFT);

                $query->where('sku', $sku);
            } else {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            }
        })
        ->orderByDesc('id')
        ->paginate(10)
        ->withQueryString();

    $products->getCollection()->transform(function ($product) {
        $product->barcode = DNS2DFacade::getBarcodePNG(
            $product->sku,
            'DATAMATRIX',
            5,
            5,
            [0, 0, 0],
            [255, 255, 255]
        );

        return $product;
    });

    return view('products.index', compact('products', 'search'));
}


public function create()
{
    return view('products.create');
}


   public function store(
        Request $request,
        SkuGenerator $skuGenerator
    ) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $product = DB::transaction(function () use ($validated, $skuGenerator) {
            $sku = $skuGenerator->next('ANT');

            return Product::create([
                ...$validated,
                'sku' => $sku,
                'is_active' => true,
            ]);
        }, 3);

        $images = $request->file('images', []);

        // Guardamos todas las imágenes primero
        foreach ($images as $index => $image) {
            $path = $image->store('products', 'public');

            $product->images()->create([
                'path' => $path,
                'alt_text' => $product->name,
                'is_primary' => $index === 0,
            ]);
        }
        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Producto registrado con SKU: ' . $product->sku);
    }
        public function show(Product $product)
        {
            $product->load('images');
            $barcode = DNS2DFacade::getBarcodePNG(
                $product->sku,
                'DATAMATRIX',
                8,
                8,
                [0, 0, 0],
                [255, 255, 255]
            );

            return view('products.show', compact('product', 'barcode'));

        }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

public function update(Request $request, Product $product)
{
    $validated = $request->validate([
        'sku' => [
            'required',
            'string',
            'max:100',
            'unique:products,sku,' . $product->id,
        ],
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        'stock' => ['required', 'integer', 'min:0'],
        'remove_images' => ['nullable', 'array'],
        'remove_images.*' => ['integer', 'exists:product_images,id'],
        'images' => ['nullable', 'array'],
        'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:2048'],
    ]);

    // 1. Actualizar datos del producto (solo los campos del form)
    $product->update([
        'sku' => $validated['sku'],
        'name' => $validated['name'],
        'description' => $validated['description'] ?? null,
        'price' => $validated['price'],
        'stock' => $validated['stock'],
    ]);

    // 2. Eliminar imágenes marcadas
    if (!empty($validated['remove_images'])) {
        $imagesToDelete = $product->images()
            ->whereIn('id', $validated['remove_images'])
            ->get();

        foreach ($imagesToDelete as $img) {
            \Storage::disk('public')->delete($img->path);
            $img->delete();
        }
    }

    // 3. Agregar nuevas imágenes (respetando el límite de 5)
    if ($request->hasFile('images')) {
        $currentCount = $product->images()->count();

        foreach ($request->file('images') as $file) {
            if ($currentCount >= 5) {
                break;
            }

            $path = $file->store('products', 'public');

            $product->images()->create([
                'path' => $path,
                'is_primary' => $currentCount === 0, // la primera es principal si no hay otras
            ]);

            $currentCount++;
        }
    }

    return redirect()
        ->route('products.index')
        ->with('success', 'Producto actualizado correctamente.');
}
public function destroy(Product $product)
{
    // Desactivar el producto sin eliminarlo de la base de datos
    $product->update([
        'is_active' => false,
    ]);

    return redirect()
        ->route('products.index')
        ->with('success', 'Producto desactivado correctamente.');
}
}
