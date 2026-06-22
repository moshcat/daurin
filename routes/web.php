<?php

use App\Http\Controllers\BahanBakuController;
use App\Http\Controllers\BahanJadiController;
use App\Http\Controllers\ClassifyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LelangController;
use App\Http\Controllers\ListingSampahController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NegosiasiController;
use App\Http\Controllers\PenawaranController;
use App\Http\Controllers\PengepulJenisController;
use App\Http\Controllers\PesananController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [MarketplaceController::class, 'index'])->name('home');
Route::get('/etalase', [MarketplaceController::class, 'etalase'])->name('etalase');

Route::get('/design-system', fn () => Inertia::render('DesignSystem'))->name('design-system');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/classify', [ClassifyController::class, 'classify'])->name('classify');

    // Lelang — ruang real-time dapat dilihat semua peran terautentikasi.
    Route::get('/lelang/{lelang}', [LelangController::class, 'show'])->name('lelang.show');

    // Marketplace bahan baku jadi (beli harga tetap) — semua peran terautentikasi.
    Route::get('/beli-bahan-jadi', [BahanJadiController::class, 'marketplace'])->name('bahanjadi.market');
    Route::post('/beli-bahan-jadi/{bahanJadi}/beli', [BahanJadiController::class, 'beli'])->name('bahanjadi.beli');

    // Lelang — aksi menawar khusus industri.
    Route::middleware('role:industri')->group(function () {
        Route::post('/lelang/{lelang}/bid', [LelangController::class, 'bid'])->name('lelang.bid');
        Route::post('/lelang/{lelang}/buyout', [LelangController::class, 'buyout'])->name('lelang.buyout');
    });

    // Rumah Tangga
    Route::prefix('rt')->name('rt.')->middleware('role:rumah_tangga')->group(function () {
        Route::get('/', [ListingSampahController::class, 'index'])->name('index');
        Route::get('/baru', [ListingSampahController::class, 'create'])->name('create');
        Route::post('/', [ListingSampahController::class, 'store'])->name('store');
        Route::delete('/{listing}', [ListingSampahController::class, 'destroy'])->name('destroy');
        Route::post('/penawaran/{penawaran}/terima', [PenawaranController::class, 'terima'])->name('penawaran.terima');
        Route::post('/penawaran/{penawaran}/tolak', [PenawaranController::class, 'tolak'])->name('penawaran.tolak');
    });

    // Pengepul
    Route::prefix('pengepul')->name('pengepul.')->middleware('role:pengepul')->group(function () {
        Route::get('/jenis', [PengepulJenisController::class, 'index'])->name('jenis.index');
        Route::post('/jenis', [PengepulJenisController::class, 'store'])->name('jenis.store');
        Route::delete('/jenis/{jenis}', [PengepulJenisController::class, 'destroy'])->name('jenis.destroy');
        Route::get('/ketersediaan', [ListingSampahController::class, 'ketersediaan'])->name('ketersediaan');
        Route::post('/klaim/{listing}', [ListingSampahController::class, 'klaim'])->name('klaim');
        Route::post('/penawaran/{listing}', [PenawaranController::class, 'ajukan'])->name('penawaran.ajukan');
        Route::get('/bahan-baku', [BahanBakuController::class, 'index'])->name('bahanbaku.index');
        Route::post('/bahan-baku/{listing}', [BahanBakuController::class, 'store'])->name('bahanbaku.store');
        Route::post('/lelang/{bahanBaku}', [LelangController::class, 'start'])->name('lelang.start');
        Route::delete('/lelang/{lelang}', [LelangController::class, 'cancel'])->name('lelang.cancel');
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
        Route::post('/pesanan/{pesanan}/bayar', [PesananController::class, 'bayar'])->name('pesanan.bayar');
        Route::get('/pesanan/{pesanan}', [NegosiasiController::class, 'show'])->name('nego.show');
        Route::post('/pesanan/{pesanan}/tawar', [NegosiasiController::class, 'tawar'])->name('nego.tawar');
        Route::post('/pesanan/{pesanan}/deal', [NegosiasiController::class, 'deal'])->name('nego.deal');
        Route::post('/pesanan/{pesanan}/batal', [NegosiasiController::class, 'batal'])->name('nego.batal');
    });
});

require __DIR__.'/settings.php';
