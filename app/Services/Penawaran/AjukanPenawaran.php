<?php

namespace App\Services\Penawaran;

use App\Enums\ListingStatus;
use App\Enums\PenawaranStatus;
use App\Models\ListingSampah;
use App\Models\PenawaranListing;
use App\Models\User;
use App\Notifications\PenawaranMasuk;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pengepul mengajukan penawaran harga untuk listing sampah RT.
 */
class AjukanPenawaran
{
    public function handle(User $pengepul, ListingSampah $listing, float $harga): PenawaranListing
    {
        $penawaran = DB::transaction(function () use ($pengepul, $listing, $harga): PenawaranListing {
            /** @var ListingSampah $listing */
            $listing = ListingSampah::whereKey($listing->id)->lockForUpdate()->firstOrFail();

            if ($listing->status !== ListingStatus::Tersedia) {
                throw ValidationException::withMessages(['listing' => 'Listing tidak tersedia.']);
            }

            $menangani = $pengepul->pengepulJenis()
                ->where('jenis_sampah', $listing->jenis_sampah->value)
                ->exists();

            if (! $menangani) {
                throw ValidationException::withMessages(['listing' => 'Anda tidak menangani jenis sampah ini.']);
            }

            $sudahAda = $listing->penawaran()
                ->where('pengepul_id', $pengepul->id)
                ->where('status', PenawaranStatus::Diajukan->value)
                ->exists();

            if ($sudahAda) {
                throw ValidationException::withMessages(['harga' => 'Anda sudah punya penawaran aktif untuk listing ini.']);
            }

            return $listing->penawaran()->create([
                'pengepul_id' => $pengepul->id,
                'harga' => $harga,
                'status' => PenawaranStatus::Diajukan->value,
            ]);
        });

        $listing->loadMissing('user');
        rescue(fn () => $listing->user?->notify(new PenawaranMasuk($penawaran)));

        return $penawaran;
    }
}
