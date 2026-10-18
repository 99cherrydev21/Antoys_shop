<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\InventoryOperationController;
use App\Http\Controllers\InventoryCountController;


Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::middleware('auth')->group(function () {
        Route::resource('products', ProductController::class);
    });
    Route::get(
    '/products/{product}/movements',
    [InventoryMovementController::class, 'index']
    )->name('inventory.movements.index');

    Route::post(
        '/products/{product}/movements',
        [InventoryMovementController::class, 'store']
    )->name('inventory.movements.store');



    Route::get('/inventory/operations/create',
        [InventoryOperationController::class, 'create']
    )->name('inventory.operations.create');

    Route::get('/inventory/operations/product',
        [InventoryOperationController::class, 'lookup']
    )->name('inventory.operations.lookup');

    Route::post('/inventory/operations',
        [InventoryOperationController::class, 'store']
    )->name('inventory.operations.store');
    Route::get('/inventory-counts', [InventoryCountController::class, 'index'])
    ->name('inventory-counts.index');

    Route::get('/inventory-counts/create', [InventoryCountController::class, 'create'])
        ->name('inventory-counts.create');

    Route::post('/inventory-counts', [InventoryCountController::class, 'store'])
        ->name('inventory-counts.store');

    Route::get('/inventory-counts/{inventoryCount}', [InventoryCountController::class, 'show'])
        ->name('inventory-counts.show');

    Route::post('/inventory-counts/{inventoryCount}/items/{item}', [InventoryCountController::class, 'updateItem'])
        ->name('inventory-counts.items.update');

     Route::get('/inventory-counts/{inventoryCount}/count', [InventoryCountController::class, 'count'])
        ->name('inventory-counts.count');

    Route::get('/inventory-counts/{inventoryCount}/lookup', [InventoryCountController::class, 'lookup'])
        ->name('inventory-counts.lookup');

    Route::post('/inventory-counts/{inventoryCount}/count', [InventoryCountController::class, 'storeCount'])
        ->name('inventory-counts.count.store');
    Route::post('/inventory-counts/{inventoryCount}/items/update-ajax', [InventoryCountController::class, 'updateItemAjax'])
    ->name('inventory-counts.items.update-ajax');
    Route::post(
    '/inventory-counts/{inventoryCount}/save-all',
    [InventoryCountController::class, 'saveAllItems']
    )->name('inventory-counts.items.save-all');
});



require __DIR__.'/auth.php';
