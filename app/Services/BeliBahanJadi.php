<?php

namespace App\Services;

use App\Enums\BahanJadiStatus;
use App\Models\BahanJadi;
use App\Models\User;
use App\Notifications\BahanJadiTerjual;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pembeli membeli bahan baku jadi (harga tetap) + bayar simulasi seketika.
 */
class BeliBahanJadi
{
    public function handle(User $pembeli, BahanJadi $bahanJadi): void
    {
        $terjual = DB::transaction(function () use ($pembeli, $bahanJadi): BahanJadi {
            /** @var BahanJadi $bahanJadi */
            $bahanJadi = BahanJadi::whereKey($bahanJadi->id)->lockForUpdate()->firstOrFail();

            if ($bahanJadi->user_id === $pembeli->id) {
                throw ValidationException::withMessages(['bahan_jadi' => 'Anda penjual barang ini.']);
            }

            if ($bahanJadi->status !== BahanJadiStatus::Tersedia) {
                throw ValidationException::withMessages(['bahan_jadi' => 'Barang sudah tidak tersedia.']);
            }

            $bahanJadi->update([
                'status' => BahanJadiStatus::Terjual->value,
                'pembeli_id' => $pembeli->id,
                'dibayar_at' => now(),
            ]);

            return $bahanJadi;
        });

        // Notifikasi ke penjual (best-effort).
        $terjual->loadMissing('user');
        rescue(fn () => $terjual->user?->notify(new BahanJadiTerjual($terjual)));
    }
}
