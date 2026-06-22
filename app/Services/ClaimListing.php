<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\ListingSampah;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClaimListing
{
    /**
     * Claim a listing for a pengepul.
     *
     * @throws ValidationException
     */
    public function handle(User $pengepul, ListingSampah $listing): void
    {
        DB::transaction(function () use ($pengepul, $listing) {
            // Refresh inside transaction to get a consistent snapshot
            $listing->refresh();

            if ($listing->status !== ListingStatus::Tersedia) {
                throw ValidationException::withMessages([
                    'listing' => 'Listing tidak tersedia.',
                ]);
            }

            if ($listing->claimed_by !== null) {
                throw ValidationException::withMessages([
                    'listing' => 'Listing sudah diklaim.',
                ]);
            }

            $handlesJenis = $pengepul->pengepulJenis()
                ->where('jenis_sampah', $listing->jenis_sampah->value)
                ->exists();

            if (! $handlesJenis) {
                throw ValidationException::withMessages([
                    'listing' => 'Anda tidak menangani jenis sampah ini.',
                ]);
            }

            $listing->update([
                'status' => ListingStatus::Diambil->value,
                'claimed_by' => $pengepul->id,
            ]);
        });
    }
}
