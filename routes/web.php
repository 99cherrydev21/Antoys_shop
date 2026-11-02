<?php

use App\Http\Controllers\InventoryCountController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\InventoryOperationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

/*
|--------------------------------------------------------------------------
| Usuarios autenticados (cualquier rol)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // "lookup" va antes de las rutas /products/{product} para que no lo capturen
    Route::get('/products/lookup', [ProductController::class, 'lookup'])->name('products.lookup');

    // Inventario físico
    // Cada inventarista solo ve los suyos; esa regla se valida en el controlador
    Route::prefix('inventory-counts')
        ->name('inventory-counts.')
        ->controller(InventoryCountController::class)
        ->group(function () {

            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');

            Route::get('/{inventoryCount}', 'show')->name('show');

            Route::get('/{inventoryCount}/count', 'count')->name('count');
            Route::post('/{inventoryCount}/count', 'storeCount')->name('count.store');

            Route::get('/{inventoryCount}/lookup', 'lookup')->name('lookup');

            // "update-ajax" va antes de "{item}" para que no lo capture esa ruta
            Route::post('/{inventoryCount}/items/update-ajax', 'updateItemAjax')->name('items.update-ajax');
            Route::post('/{inventoryCount}/items/{item}', 'updateItem')->name('items.update');
            Route::post('/{inventoryCount}/save-all', 'saveAllItems')->name('items.save-all');

            // Solo jefe
            Route::middleware('can:manage-products')->group(function () {
                Route::post('/{inventoryCount}/apply', 'apply')->name('apply');
                Route::patch('/{inventoryCount}/archive', 'archive')->name('archive');
                Route::patch('/{inventoryCount}/restore', 'restore')->name('restore');
            });
        });

    /*
    |----------------------------------------------------------------------
    | Solo jefe: catálogo, entradas/salidas y movimientos
    |----------------------------------------------------------------------
    */
    Route::middleware('can:manage-products')->group(function () {

        // Registrar, modificar y eliminar productos
        Route::resource('products', ProductController::class)
            ->only(['create', 'store', 'edit', 'update', 'destroy']);

        // Movimientos por producto
        Route::get('/products/{product}/movements', [InventoryMovementController::class, 'index'])
            ->name('inventory.movements.index');
        Route::post('/products/{product}/movements', [InventoryMovementController::class, 'store'])
            ->name('inventory.movements.store');

        // Entradas y salidas
        Route::get('/inventory/operations/create', [InventoryOperationController::class, 'create'])
            ->name('inventory.operations.create');
        Route::get('/inventory/operations/product', [InventoryOperationController::class, 'lookup'])
            ->name('inventory.operations.lookup');
        Route::post('/inventory/operations', [InventoryOperationController::class, 'store'])
            ->name('inventory.operations.store');
    });

    // Consultar productos (todos los roles).
    // Va DESPUÉS del grupo del jefe para que /products/create no lo capture /products/{product}
    Route::resource('products', ProductController::class)
        ->only(['index', 'show']);

    /*
    |----------------------------------------------------------------------
    | Solo jefe: gestión de usuarios
    |----------------------------------------------------------------------
    */
    Route::middleware('can:manage-users')->group(function () {
        Route::resource('users', UserController::class)
            ->only(['index', 'store', 'update']);

        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');
    });
});

require __DIR__ . '/auth.php';
