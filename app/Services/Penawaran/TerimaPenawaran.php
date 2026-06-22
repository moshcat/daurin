<?php

namespace App\Services\Penawaran;

use App\Enums\ListingStatus;
use App\Enums\PenawaranStatus;
use App\Models\ListingSampah;
use App\Models\PenawaranListing;
use App\Models\User;
use App\Notifications\PenawaranDiterima;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * RT menerima penawaran pengepul → listing diklaim pengepul dengan harga sepakat,
 * penawaran lain pada listing yang sama otomatis ditolak.
 */
class TerimaPenawaran
{
    public function handle(User $rt, PenawaranListing $penawaran): void
    {
        $diterima = DB::transaction(function () use ($rt, $penawaran): PenawaranListing {
            /** @var PenawaranListing $penawaran */
            $penawaran = PenawaranListing::whereKey($penawaran->id)->lockForUpdate()->firstOrFail();
            /** @var ListingSampah $listing */
            $listing = ListingSampah::whereKey($penawaran->listing_sampah_id)->lockForUpdate()->firstOrFail();

            if ($listing->user_id !== $rt->id) {
                throw ValidationException::withMessages(['penawaran' => 'Ini bukan listing Anda.']);
            }
            if ($penawaran->status !== PenawaranStatus::Diajukan) {
                throw ValidationException::withMessages(['penawaran' => 'Penawaran sudah tidak aktif.']);
            }
            if ($listing->status !== ListingStatus::Tersedia) {
                throw ValidationException::withMessages(['penawaran' => 'Listing sudah tidak tersedia.']);
            }

            $penawaran->update(['status' => PenawaranStatus::Diterima->value]);

            // Tolak penawaran pesaing pada listing yang sama.
            $listing->penawaran()
                ->whereKeyNot($penawaran->id)
                ->where('status', PenawaranStatus::Diajukan->value)
                ->update(['status' => PenawaranStatus::Ditolak->value]);

            // Listing menjadi milik pengepul dengan harga kesepakatan.
            $listing->update([
                'status' => ListingStatus::Diambil->value,
                'claimed_by' => $penawaran->pengepul_id,
                'harga' => $penawaran->harga,
            ]);

            return $penawaran;
        });

        $diterima->loadMissing('pengepul');
        rescue(fn () => $diterima->pengepul?->notify(new PenawaranDiterima($diterima)));
    }
}
