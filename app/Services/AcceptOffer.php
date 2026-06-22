<?php

namespace App\Services;

use App\Enums\BahanBakuStatus;
use App\Enums\PesananStatus;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptOffer
{
    /**
     * Finalise a negotiation as a deal at the given price.
     *
     * @throws ValidationException
     */
    public function handle(Pesanan $pesanan, float $harga): void
    {
        DB::transaction(function () use ($pesanan, $harga) {
            $pesanan->refresh();

            if ($pesanan->status !== PesananStatus::Nego) {
                throw ValidationException::withMessages([
                    'pesanan' => 'Pesanan tidak dalam status negosiasi.',
                ]);
            }

            $pesanan->update([
                'status' => PesananStatus::Deal->value,
                'harga_sepakat' => $harga,
            ]);

            $pesanan->bahanBaku->update([
                'status' => BahanBakuStatus::Terjual->value,
            ]);
        });
    }
}
