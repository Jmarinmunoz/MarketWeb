<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminStoreContextController;
use App\Http\Controllers\ModulePlaceholderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\BusinessSettingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes(['register' => false]);

Route::get('/home', [DashboardController::class, 'index'])
    ->middleware(['auth', 'active'])
    ->name('home');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])
        ->middleware('role:Administrador')
        ->name('dashboard.admin');

    Route::get('/dashboard/vendedor', [DashboardController::class, 'seller'])
        ->middleware('role:Administrador,Vendedor')
        ->name('dashboard.seller');

    Route::middleware('role:Administrador')->group(function (): void {
        Route::put('/admin/local-contexto', [AdminStoreContextController::class, 'update'])->name('admin.store-context.update');
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::get('/usuarios/crear', [UserController::class, 'create'])->name('users.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::put('/usuarios/{user}/negocio', [UserController::class, 'updateVendorBusiness'])->name('users.vendor-business.update');

        Route::get('/reportes', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/productos/{product}/editar', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/productos/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/productos/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    Route::middleware('role:Vendedor')->group(function (): void {
        Route::get('/configuracion', [BusinessSettingController::class, 'edit'])->name('settings.index');
        Route::put('/configuracion', [BusinessSettingController::class, 'update'])->name('settings.update');
        Route::put('/configuracion/contrasena', [BusinessSettingController::class, 'updatePassword'])->name('settings.password');
    });

    Route::middleware('role:Administrador,Vendedor')->group(function (): void {
        Route::get('/categorias', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categorias/crear', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categorias', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categorias/{category}/editar', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categorias/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::get('/productos', [ProductController::class, 'index'])->name('products.index');
        Route::get('/productos/crear', [ProductController::class, 'create'])->name('products.create');
        Route::post('/productos', [ProductController::class, 'store'])->name('products.store');
        Route::put('/productos/{product}/precio', [ProductController::class, 'updatePrice'])->name('products.update-price');
        Route::get('/productos/{product}/panel', [ProductController::class, 'panel'])->name('products.panel');
        Route::get('/productos/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('/ventas', [SaleController::class, 'index'])->name('sales.index');
        Route::post('/ventas/agregar', [SaleController::class, 'addByBarcode'])->name('sales.add-by-barcode');
        Route::post('/ventas/agregar-producto', [SaleController::class, 'addByProduct'])->name('sales.add-by-product');
        Route::get('/ventas/buscar-productos', [SaleController::class, 'searchProducts'])->name('sales.search-products');
        Route::put('/ventas/item/{productId}', [SaleController::class, 'updateItem'])->name('sales.update-item');
        Route::delete('/ventas/item/{productId}', [SaleController::class, 'removeItem'])->name('sales.remove-item');
        Route::delete('/ventas/carrito', [SaleController::class, 'clearCart'])->name('sales.clear-cart');
        Route::post('/ventas/confirmar', [SaleController::class, 'checkout'])->name('sales.checkout');
        Route::get('/inventario', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/movimientos-stock', [StockMovementController::class, 'index'])->name('stock-movements.index');
        Route::get('/movimientos-stock/buscar-productos', [StockMovementController::class, 'searchProducts'])->name('stock-movements.search-products');
        Route::post('/movimientos-stock', [StockMovementController::class, 'store'])->name('stock-movements.store');
        Route::get('/historial-ventas', [SaleController::class, 'history'])->name('sales-history.index');
        Route::get('/historial-ventas/{sale}', [SaleController::class, 'historyDetail'])->name('sales-history.detail');
    });
});
