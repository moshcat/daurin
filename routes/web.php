<?php

use App\Http\Controllers\BahanBakuController;
use App\Http\Controllers\BahanJadiController;
use App\Http\Controllers\ClassifyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ListingSampahController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NegosiasiController;
use App\Http\Controllers\PengepulJenisController;
use App\Http\Controllers\PesananController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [MarketplaceController::class, 'index'])->name('home');

Route::get('/design-system', fn () => Inertia::render('DesignSystem'))->name('design-system');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/classify', [ClassifyController::class, 'classify'])->name('classify');

    // Rumah Tangga
    Route::prefix('rt')->name('rt.')->middleware('role:rumah_tangga')->group(function () {
        Route::get('/', [ListingSampahController::class, 'index'])->name('index');
        Route::get('/baru', [ListingSampahController::class, 'create'])->name('create');
        Route::post('/', [ListingSampahController::class, 'store'])->name('store');
        Route::delete('/{listing}', [ListingSampahController::class, 'destroy'])->name('destroy');
    });

    // Pengepul
    Route::prefix('pengepul')->name('pengepul.')->middleware('role:pengepul')->group(function () {
        Route::get('/jenis', [PengepulJenisController::class, 'index'])->name('jenis.index');
        Route::post('/jenis', [PengepulJenisController::class, 'store'])->name('jenis.store');
        Route::delete('/jenis/{jenis}', [PengepulJenisController::class, 'destroy'])->name('jenis.destroy');
        Route::get('/ketersediaan', [ListingSampahController::class, 'ketersediaan'])->name('ketersediaan');
        Route::post('/klaim/{listing}', [ListingSampahController::class, 'klaim'])->name('klaim');
        Route::get('/bahan-baku', [BahanBakuController::class, 'index'])->name('bahanbaku.index');
        Route::post('/bahan-baku/{listing}', [BahanBakuController::class, 'store'])->name('bahanbaku.store');
        Route::get('/pesanan/{pesanan}', [NegosiasiController::class, 'showForPengepul'])->name('nego.show');
        Route::post('/pesanan/{pesanan}/balas', [NegosiasiController::class, 'balas'])->name('nego.balas');
        Route::post('/pesanan/{pesanan}/deal', [NegosiasiController::class, 'dealByPengepul'])->name('nego.deal');
    });

    // Industri
    Route::prefix('industri')->name('industri.')->middleware('role:industri')->group(function () {
        Route::get('/', [BahanBakuController::class, 'indexForIndustri'])->name('index');
        Route::get('/bahan-jadi', [BahanJadiController::class, 'index'])->name('bahanjadi.index');
        Route::post('/bahan-jadi', [BahanJadiController::class, 'store'])->name('bahanjadi.store');
        Route::post('/pesanan', [PesananController::class, 'store'])->name('pesanan.store');
        Route::get('/pesanan/{pesanan}', [NegosiasiController::class, 'show'])->name('nego.show');
        Route::post('/pesanan/{pesanan}/tawar', [NegosiasiController::class, 'tawar'])->name('nego.tawar');
        Route::post('/pesanan/{pesanan}/deal', [NegosiasiController::class, 'deal'])->name('nego.deal');
        Route::post('/pesanan/{pesanan}/batal', [NegosiasiController::class, 'batal'])->name('nego.batal');
    });
});

require __DIR__.'/settings.php';
