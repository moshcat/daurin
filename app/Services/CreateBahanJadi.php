<?php

namespace App\Services;

use App\Enums\PesananStatus;
use App\Models\BahanJadi;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBahanJadi
{
    /**
     * Process a completed deal (pesanan) into a sellable finished material.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(User $industri, Pesanan $pesanan, array $data): BahanJadi
    {
        if ((int) $pesanan->industri_id !== $industri->id) {
            throw ValidationException::withMessages([
                'pesanan' => 'Anda bukan pemilik pesanan ini.',
            ]);
        }

        if ($pesanan->status !== PesananStatus::Deal) {
            throw ValidationException::withMessages([
                'pesanan' => 'Hanya pesanan berstatus deal yang bisa diolah jadi bahan baku jadi.',
            ]);
        }

        return DB::transaction(function () use ($industri, $pesanan, $data): BahanJadi {
            return BahanJadi::create(array_merge($data, [
                'user_id' => $industri->id,
                'source_pesanan_id' => $pesanan->id,
            ]));
        });
    }
}
