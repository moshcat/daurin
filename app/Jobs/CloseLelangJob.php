<?php

namespace App\Jobs;

use App\Models\Lelang;
use App\Services\Lelang\CloseLelang;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Menutup lelang saat deadline tercapai.
 *
 * Di-dispatch dengan delay = waktu_selesai saat lelang dibuka. ShouldBeUnique
 * mencegah penutupan ganda bila job tertunda dan scheduler jaring-pengaman
 * sama-sama menyala untuk lelang yang sama.
 */
class CloseLelangJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30];

    public function __construct(public int $lelangId) {}

    public function uniqueId(): string
    {
        return (string) $this->lelangId;
    }

    public function handle(CloseLelang $closeLelang): void
    {
        $lelang = Lelang::find($this->lelangId);

        if ($lelang !== null) {
            $closeLelang->handle($lelang);
        }
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
