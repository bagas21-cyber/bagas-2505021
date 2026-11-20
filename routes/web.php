<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockInController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('categories', \App\Http\Controllers\CategoryController::class);
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
    Route::resource('products', ProductController::class);
    Route::get('stock-ins/autocomplete', [StockInController::class, 'autocomplete'])->name('stock-ins.autocomplete');
    Route::post('stock-ins/{stock_in}/cancel', [StockInController::class, 'cancel'])->name('stock-ins.cancel');
    Route::resource('stock-ins', StockInController::class)->only(['index', 'create', 'store', 'show']);

    Route::view('/sales', 'placeholder', ['title' => 'Penjualan'])->name('sales.index');
    foreach ([
        'reports' => 'Laporan',
        'settings' => 'Pengaturan',
    ] as $uri => $label) {
        Route::view('/'.$uri, 'placeholder', ['title' => $label])->name($uri.'.index');
    }
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
