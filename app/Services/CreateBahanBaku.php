<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\BahanBaku;
use App\Models\ListingSampah;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBahanBaku
{
    /**
     * Convert a claimed listing into a bahan baku entry.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(User $pengepul, ListingSampah $listing, array $data): BahanBaku
    {
        if ((int) $listing->claimed_by !== $pengepul->id) {
            throw ValidationException::withMessages([
                'listing' => 'Anda tidak memiliki klaim atas listing ini.',
            ]);
        }

        return DB::transaction(function () use ($pengepul, $listing, $data): BahanBaku {
            $bahanBaku = BahanBaku::create(array_merge($data, [
                'user_id' => $pengepul->id,
                'source_listing_id' => $listing->id,
            ]));

            $listing->update(['status' => ListingStatus::Terjual->value]);

            return $bahanBaku;
        });
    }
}
