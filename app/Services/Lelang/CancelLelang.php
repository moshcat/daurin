<?php

namespace App\Services\Lelang;

use App\Enums\BahanBakuStatus;
use App\Enums\LelangStatus;
use App\Events\LelangClosed;
use App\Models\Lelang;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pengepul membatalkan lelang — hanya boleh bila belum ada satu pun tawaran.
 */
class CancelLelang
{
    public function handle(Lelang $lelang): void
    {
        DB::transaction(function () use ($lelang): void {
            /** @var Lelang $lelang */
            $lelang = Lelang::whereKey($lelang->id)->lockForUpdate()->firstOrFail();

            if ($lelang->status !== LelangStatus::Berlangsung) {
                throw ValidationException::withMessages(['lelang' => 'Negosiasi tidak sedang berlangsung.']);
            }

            if ($lelang->bids()->exists()) {
                throw ValidationException::withMessages([
                    'lelang' => 'Tidak bisa dibatalkan: sudah ada penawaran masuk.',
                ]);
            }

            $lelang->update(['status' => LelangStatus::Batal->value]);
            $lelang->bahanBaku->update(['status' => BahanBakuStatus::Tersedia->value]);
        });

        rescue(fn () => event(new LelangClosed($lelang->refresh())));
    }
}
