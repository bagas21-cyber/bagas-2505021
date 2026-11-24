<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('categories', CategoryController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('products', ProductController::class);
    Route::get('stock-ins/autocomplete', [StockInController::class, 'autocomplete'])->name('stock-ins.autocomplete');
    Route::post('stock-ins/{stock_in}/cancel', [StockInController::class, 'cancel'])->name('stock-ins.cancel');
    Route::resource('stock-ins', StockInController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('stock/low', [StockController::class, 'low'])->name('stock.low');
    Route::get('stock/movements', [StockController::class, 'movements'])->name('stock.movements');
    Route::get('stock/adjustments/create', [StockController::class, 'create'])->name('stock.adjustments.create');
    Route::post('stock/adjustments', [StockController::class, 'store'])->name('stock.adjustments.store');
    Route::get('stock/adjustments/{stockOpname}', [StockController::class, 'show'])->name('stock.adjustments.show');
    Route::get('sales/autocomplete', [SaleController::class, 'autocomplete'])->name('sales.autocomplete');
    Route::post('sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');
    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
    Route::middleware('admin')->group(function (): void {
        Route::redirect('/reports', '/reports/sales')->name('reports.index');
        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/stock', [ReportController::class, 'stock'])->name('reports.stock');
        Route::get('/reports/profit', [ReportController::class, 'profit'])->name('reports.profit');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::put('/settings/store', [SettingsController::class, 'updateStore'])->name('settings.store.update');
        Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
        Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
        Route::patch('/settings/users/{user}', [SettingsController::class, 'updateUser'])->name('settings.users.update');
        Route::delete('/settings/users/{user}', [SettingsController::class, 'destroyUser'])->name('settings.users.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
