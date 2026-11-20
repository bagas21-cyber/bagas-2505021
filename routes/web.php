<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    foreach ([
        'products' => 'Produk',
        'categories' => 'Kategori',
        'suppliers' => 'Supplier',
        'stock-ins' => 'Stok Masuk',
        'sales' => 'Penjualan',
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
