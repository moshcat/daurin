<?php

namespace Database\Factories;

use App\Models\Lelang;
use App\Models\LelangBid;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LelangBid>
 */
class LelangBidFactory extends Factory
{
    protected $model = LelangBid::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lelang_id' => Lelang::factory(),
            'industri_id' => User::factory()->industri(),
            'harga' => fake()->numberBetween(20, 120) * 1000,
            'is_buyout' => false,
        ];
    }

    public function buyout(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_buyout' => true,
        ]);
    }
}
