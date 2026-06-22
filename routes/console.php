<?php

use App\Models\Lelang;
use App\Services\Lelang\CloseLelang;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jaring-pengaman: tutup lelang yang sudah lewat deadline tapi belum tertutup
// (mis. bila CloseLelangJob gagal / queue worker mati).
Schedule::call(function (CloseLelang $closeLelang): void {
    Lelang::expired()->each(fn (Lelang $lelang) => $closeLelang->handle($lelang));
})->name('tutup-lelang-kedaluwarsa')->everyMinute()->withoutOverlapping();
