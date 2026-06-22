<?php

namespace App\Services\Lelang;

use App\Events\BidPlaced;
use App\Models\Lelang;
use App\Models\LelangBid;
use App\Models\User;
use App\Notifications\TawaranBaru;
use App\Notifications\TawaranDilampaui;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jantung lelang: industri menaikkan tawaran.
 *
 * Seluruh validasi dijalankan di dalam transaksi dengan lockForUpdate pada baris
 * lelang sehingga tawaran bersamaan diserialisasi (anti race condition).
 */
class PlaceBid
{
    public function __construct(private CloseLelang $closeLelang) {}

    public function handle(Lelang $lelang, User $industri, float $harga, bool $isBuyout = false): LelangBid
    {
        $prevBidderId = null;

        $bid = DB::transaction(function () use ($lelang, $industri, $harga, $isBuyout, &$prevBidderId): LelangBid {
            /** @var Lelang $lelang */
            $lelang = Lelang::whereKey($lelang->id)->lockForUpdate()->firstOrFail();

            if (! $lelang->isOpen()) {
                throw ValidationException::withMessages(['lelang' => 'Negosiasi sudah ditutup.']);
            }

            $lelang->loadMissing('bahanBaku');
            if ($lelang->bahanBaku->user_id === $industri->id) {
                throw ValidationException::withMessages(['lelang' => 'Anda penjual barang ini, tidak bisa menawar.']);
            }

            // Buyout harus tepat seharga harga_buyout (bila disetel).
            if ($isBuyout) {
                if ($lelang->harga_buyout === null) {
                    throw ValidationException::withMessages(['lelang' => 'Penawaran ini tidak menyediakan beli-langsung.']);
                }
                $harga = (float) $lelang->harga_buyout;
            } else {
                $minimal = (float) $lelang->minimalBidBerikutnya();
                if ($harga < $minimal) {
                    throw ValidationException::withMessages([
                        'harga' => 'Tawaran minimal Rp '.number_format($minimal, 0, ',', '.').'.',
                    ]);
                }
            }

            $bid = $lelang->bids()->create([
                'industri_id' => $industri->id,
                'harga' => $harga,
                'is_buyout' => $isBuyout,
            ]);

            // Catat penawar pemimpin sebelumnya (untuk notifikasi "dilampaui").
            $prevBidderId = $lelang->highest_bid_id
                ? LelangBid::whereKey($lelang->highest_bid_id)->value('industri_id')
                : null;

            $lelang->highest_bid_id = $bid->id;

            // Anti-sniping: perpanjang +60 dtk bila kurang dari 60 dtk tersisa.
            $sisaDetik = now()->diffInSeconds($lelang->waktu_selesai, false);
            if (! $isBuyout && $sisaDetik < 60) {
                $lelang->waktu_selesai = now()->addSeconds(60);
            }

            $lelang->save();

            return $bid;
        });

        $lelang->refresh()->loadMissing('highestBid');

        // Broadcast tawaran baru setelah commit (best-effort: bid tetap sah
        // meski server Reverb sedang tidak aktif). event() menyiarkan inline
        // sehingga kegagalan koneksi tertangkap rescue.
        rescue(fn () => event(new BidPlaced($lelang, $bid)));

        // Notifikasi email (queued, best-effort) — lewati saat buyout karena
        // CloseLelang akan mengirim notifikasi hasil akhir.
        if (! $isBuyout) {
            $lelang->loadMissing('bahanBaku.user');
            $seller = $lelang->bahanBaku->user;
            rescue(fn () => $seller?->notify(new TawaranBaru($lelang, (float) $bid->harga)));

            if ($prevBidderId !== null && (int) $prevBidderId !== $industri->id) {
                $prevBidder = User::find($prevBidderId);
                rescue(fn () => $prevBidder?->notify(new TawaranDilampaui($lelang, (float) $bid->harga)));
            }
        }

        // Buyout langsung menutup lelang.
        if ($isBuyout) {
            $this->closeLelang->handle($lelang, force: true);
        }

        return $bid;
    }
}
