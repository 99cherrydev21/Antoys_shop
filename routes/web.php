
<?php

use App\Http\Controllers\InventoryCountController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\InventoryOperationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Página principal
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware('auth');


/*
|--------------------------------------------------------------------------
| Usuarios autenticados (cualquier rol)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Perfil
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');


    /*
    |--------------------------------------------------------------------------
    | Punto de venta
    |--------------------------------------------------------------------------
    */

    Route::prefix('sales')
        ->name('sales.')
        ->controller(SaleController::class)
        ->group(function () {

            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');

            Route::get('/history', 'history')->name('history');

            Route::get('/{sale}', 'show')->name('show');
            Route::get('/{sale}/edit', 'edit')->name('edit');

            Route::put('/{sale}', 'update')->name('update');
            Route::delete('/{sale}', 'destroy')->name('destroy');
        });


    /*
    |--------------------------------------------------------------------------
    | Inventario físico
    |--------------------------------------------------------------------------
    | Cada inventarista solo ve los suyos.
    | Esa regla se valida en el controlador.
    */

    Route::prefix('inventory-counts')
        ->name('inventory-counts.')
        ->controller(InventoryCountController::class)
        ->group(function () {

            // Consultar y crear conteos
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');

            // Consultar un conteo
            Route::get('/{inventoryCount}', 'show')->name('show');

            // Capturar conteo físico
            Route::get('/{inventoryCount}/count', 'count')
                ->name('count');

            Route::post('/{inventoryCount}/count', 'storeCount')
                ->name('count.store');

            // Buscar productos
            Route::get('/{inventoryCount}/lookup', 'lookup')
                ->name('lookup');

            // Actualizar productos del conteo
            Route::post('/{inventoryCount}/items/update-ajax', 'updateItemAjax')
                ->name('items.update-ajax');

            Route::post('/{inventoryCount}/items/{item}', 'updateItem')
                ->name('items.update');

            Route::post('/{inventoryCount}/save-all', 'saveAllItems')
                ->name('items.save-all');


            /*
            |--------------------------------------------------------------------------
            | Solo jefe: aplicar y administrar conteos
            |--------------------------------------------------------------------------
            */

            Route::middleware('can:manage-products')->group(function () {

                Route::post('/{inventoryCount}/apply', 'apply')
                    ->name('apply');

                Route::patch('/{inventoryCount}/archive', 'archive')
                    ->name('archive');

                Route::patch('/{inventoryCount}/restore', 'restore')
                    ->name('restore');
            });
        });


    /*
    |--------------------------------------------------------------------------
    | Consulta de productos (todos los roles)
    |--------------------------------------------------------------------------
    */

    // Debe ir antes de las rutas dinámicas de productos.
    Route::get('/products/lookup', [ProductController::class, 'lookup'])
        ->name('products.lookup');

    Route::resource('products', ProductController::class)
        ->only(['index', 'show']);


    /*
    |--------------------------------------------------------------------------
    | Solo jefe: catálogo, entradas, salidas y movimientos
    |--------------------------------------------------------------------------
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


        // Entradas y salidas de inventario
        Route::get('/inventory/operations/create', [InventoryOperationController::class, 'create'])
            ->name('inventory.operations.create');

        Route::get('/inventory/operations/product', [InventoryOperationController::class, 'lookup'])
            ->name('inventory.operations.lookup');

        Route::post('/inventory/operations', [InventoryOperationController::class, 'store'])
            ->name('inventory.operations.store');
    });


    /*
    |--------------------------------------------------------------------------
    | Solo jefe: gestión de usuarios
    |--------------------------------------------------------------------------
    */

    Route::middleware('can:manage-users')->group(function () {

        Route::resource('users', UserController::class)
            ->only(['index', 'store', 'update']);

        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');
    });


    /*
    |--------------------------------------------------------------------------
    | Solo jefe: reportes
    |--------------------------------------------------------------------------
    */

    Route::middleware('can:manage-products')
        ->prefix('reports')
        ->name('reports.')
        ->controller(ReportController::class)
        ->group(function () {

            Route::redirect('/', '/reports/sales');

            // Reporte de ventas
            Route::get('/sales', 'sales')
                ->name('sales');

            Route::get('/sales/export', 'salesExport')
                ->name('sales.export');

            // Reporte de inventario
            Route::get('/inventory', 'inventory')
                ->name('inventory');

            Route::get('/inventory/export', 'inventoryExport')
                ->name('inventory.export');

            // Reporte de movimientos
            Route::get('/movements', 'movements')
                ->name('movements');

            Route::get('/movements/export', 'movementsExport')
                ->name('movements.export');
        });
// ========================================
// MANTENIMIENTO DEL SISTEMA
// ========================================

Route::prefix('maintenance')
    ->name('maintenance.')
    ->controller(\App\Http\Controllers\MaintenanceController::class)
    ->group(function () {

        // Rutas accesibles para quienes pueden gestionar mantenimiento (Jefe o Técnico)
        Route::middleware('can:manage-maintenance')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/history', 'history')->name('history');
        });

        // Crear solicitud: solo el jefe
        Route::post('/', 'store')
            ->name('store')
            ->middleware('can:approve-maintenance'); // O el permiso que aplique para crear

        // Autorizar: solo el jefe
        Route::patch('/{maintenanceRequest}/approve', 'approve')
            ->name('approve')
            ->middleware('can:approve-maintenance');

        // Ejecutar respaldos / mantenimiento: solo técnicos (usando tu gate execute-maintenance)
        Route::middleware('can:execute-maintenance')->group(function () {
            Route::post('/{maintenanceRequest}/backup', 'generateBackup')
                ->name('backup.generate');

            Route::get('/{maintenanceRequest}/backup/download', 'downloadBackup')
                ->name('backup.download');

            Route::post('/{maintenanceRequest}/backup/confirm', 'confirmBackup')
                ->name('backup.confirm');
        });
                    // Solicitar restablecimiento: solo jefe
            Route::post('/', 'store')->name('store');

            // Autorizar restablecimiento: solo jefe
            Route::patch('/{maintenanceRequest}/approve', 'approve')
                ->name('approve');

            // Ejecutar restablecimiento: técnico asignado
            Route::post('/{maintenanceRequest}/execute', 'execute')
                ->name('execute');
                });
});
/*
|--------------------------------------------------------------------------
| Autenticación
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
