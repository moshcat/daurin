<?php

namespace App\Services\Lelang;

use App\Enums\BahanBakuStatus;
use App\Enums\LelangStatus;
use App\Jobs\CloseLelangJob;
use App\Models\BahanBaku;
use App\Models\Lelang;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pengepul membuka lelang atas bahan bakunya.
 *
 * @phpstan-type StartLelangData array{
 *     harga_awal: float,
 *     kelipatan?: float,
 *     durasi_menit?: int,
 *     harga_reserve?: float|null,
 *     harga_buyout?: float|null
 * }
 */
class StartLelang
{
    /**
     * @param  StartLelangData  $data
     */
    public function handle(BahanBaku $bahanBaku, array $data): Lelang
    {
        $lelang = DB::transaction(function () use ($bahanBaku, $data): Lelang {
            /** @var BahanBaku $bahanBaku */
            $bahanBaku = BahanBaku::whereKey($bahanBaku->id)->lockForUpdate()->firstOrFail();

            if ($bahanBaku->status !== BahanBakuStatus::Tersedia) {
                throw ValidationException::withMessages([
                    'bahan_baku' => 'Bahan baku tidak tersedia untuk dinegosiasikan.',
                ]);
            }

            $durasiMenit = (int) ($data['durasi_menit'] ?? 1440); // default 24 jam

            $lelang = Lelang::create([
                'bahan_baku_id' => $bahanBaku->id,
                'harga_awal' => $data['harga_awal'],
                'kelipatan' => $data['kelipatan'] ?? 1000,
                'harga_reserve' => $data['harga_reserve'] ?? null,
                'harga_buyout' => $data['harga_buyout'] ?? null,
                'status' => LelangStatus::Berlangsung->value,
                'waktu_mulai' => now(),
                'waktu_selesai' => now()->addMinutes($durasiMenit),
            ]);

            $bahanBaku->update(['status' => BahanBakuStatus::Dilelang->value]);

            return $lelang;
        });

        // Jadwalkan penutupan otomatis tepat di deadline.
        CloseLelangJob::dispatch($lelang->id)->delay($lelang->waktu_selesai);

        return $lelang;
    }
}
