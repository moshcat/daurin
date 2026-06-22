<?php

namespace Database\Factories;

use App\Enums\LelangStatus;
use App\Models\BahanBaku;
use App\Models\Lelang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lelang>
 */
class LelangFactory extends Factory
{
    protected $model = Lelang::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hargaAwal = fake()->numberBetween(15, 80) * 1000;

        return [
            'bahan_baku_id' => BahanBaku::factory()->dilelang(),
            'harga_awal' => $hargaAwal,
            'kelipatan' => 1000,
            'harga_reserve' => null,
            'harga_buyout' => null,
            'status' => LelangStatus::Berlangsung->value,
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addHours(24),
            'highest_bid_id' => null,
            'pemenang_id' => null,
            'harga_final' => null,
            'pesanan_id' => null,
        ];
    }

    /** Auction whose deadline has already passed (ready to close). */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'waktu_mulai' => now()->subHours(25),
            'waktu_selesai' => now()->subMinute(),
        ]);
    }

    /** Auction that is about to end (for anti-snipe tests). */
    public function endingSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'waktu_selesai' => now()->addSeconds(30),
        ]);
    }

    public function withBuyout(int $harga): static
    {
        return $this->state(fn (array $attributes) => [
            'harga_buyout' => $harga,
        ]);
    }

    public function withReserve(int $harga): static
    {
        return $this->state(fn (array $attributes) => [
            'harga_reserve' => $harga,
        ]);
    }
}
