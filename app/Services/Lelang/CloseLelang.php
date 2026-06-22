<?php

namespace App\Services\Lelang;

use App\Enums\BahanBakuStatus;
use App\Enums\LelangStatus;
use App\Enums\PesananStatus;
use App\Events\LelangClosed;
use App\Models\Lelang;
use App\Models\Pesanan;
use App\Notifications\NegosiasiGagal;
use App\Notifications\NegosiasiMenang;
use App\Notifications\NegosiasiTerjualPenjual;
use Illuminate\Support\Facades\DB;

/**
 * Menutup lelang: tentukan pemenang → buat pesanan deal (settlement) untuk
 * rantai bahan_jadi, atau tandai gagal bila tanpa bid / reserve tak tercapai.
 */
class CloseLelang
{
    public function handle(Lelang $lelang, bool $force = false): void
    {
        $closed = DB::transaction(function () use ($lelang, $force): ?Lelang {
            /** @var Lelang $lelang */
            $lelang = Lelang::whereKey($lelang->id)->lockForUpdate()->firstOrFail();

            // Idempoten: hanya lelang yang masih berlangsung yang bisa ditutup.
            if ($lelang->status !== LelangStatus::Berlangsung) {
                return null;
            }

            // Belum waktunya (kecuali dipaksa, mis. dari buyout).
            if (! $force && $lelang->waktu_selesai->isFuture()) {
                return null;
            }

            $lelang->loadMissing(['highestBid', 'bahanBaku']);
            $top = $lelang->highestBid;

            // Tanpa bid atau di bawah reserve → gagal, bahan baku kembali tersedia.
            $reserveTakTercapai = $lelang->harga_reserve !== null
                && ($top === null || (float) $top->harga < (float) $lelang->harga_reserve);

            if ($top === null || $reserveTakTercapai) {
                $lelang->update(['status' => LelangStatus::Gagal->value]);
                $lelang->bahanBaku->update(['status' => BahanBakuStatus::Tersedia->value]);

                return $lelang;
            }

            // Pemenang → buat settlement (pesanan deal) untuk rantai bahan_jadi.
            $pesanan = Pesanan::create([
                'bahan_baku_id' => $lelang->bahan_baku_id,
                'lelang_id' => $lelang->id,
                'industri_id' => $top->industri_id,
                'status' => PesananStatus::Deal->value,
                'harga_sepakat' => $top->harga,
            ]);

            $lelang->update([
                'status' => LelangStatus::Selesai->value,
                'pemenang_id' => $top->industri_id,
                'harga_final' => $top->harga,
                'pesanan_id' => $pesanan->id,
            ]);
            $lelang->bahanBaku->update(['status' => BahanBakuStatus::Terjual->value]);

            return $lelang;
        });

        // Broadcast + notifikasi email setelah commit (best-effort).
        if ($closed !== null) {
            $closed->refresh()->loadMissing(['bahanBaku.user', 'pemenang']);

            rescue(fn () => event(new LelangClosed($closed)));

            if ($closed->status === LelangStatus::Selesai) {
                rescue(fn () => $closed->pemenang?->notify(new NegosiasiMenang($closed)));
                rescue(fn () => $closed->bahanBaku->user?->notify(new NegosiasiTerjualPenjual($closed)));
            } else {
                rescue(fn () => $closed->bahanBaku->user?->notify(new NegosiasiGagal($closed)));
            }
        }
    }
}
