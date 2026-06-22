<?php

namespace App\Services\Penawaran;

use App\Enums\PenawaranStatus;
use App\Models\PenawaranListing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * RT menolak penawaran pengepul.
 */
class TolakPenawaran
{
    public function handle(User $rt, PenawaranListing $penawaran): void
    {
        DB::transaction(function () use ($rt, $penawaran): void {
            /** @var PenawaranListing $penawaran */
            $penawaran = PenawaranListing::whereKey($penawaran->id)->lockForUpdate()->firstOrFail();
            $penawaran->loadMissing('listing');

            if ($penawaran->listing->user_id !== $rt->id) {
                throw ValidationException::withMessages(['penawaran' => 'Ini bukan listing Anda.']);
            }
            if ($penawaran->status !== PenawaranStatus::Diajukan) {
                throw ValidationException::withMessages(['penawaran' => 'Penawaran sudah tidak aktif.']);
            }

            $penawaran->update(['status' => PenawaranStatus::Ditolak->value]);
        });
    }
}
