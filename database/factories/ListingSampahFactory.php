<?php

namespace Database\Factories;

use App\Enums\JenisSampah;
use App\Enums\ListingStatus;
use App\Models\ListingSampah;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListingSampah>
 */
class ListingSampahFactory extends Factory
{
    protected $model = ListingSampah::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement(JenisSampah::cases());

        return [
            'user_id' => User::factory(),
            'jenis_sampah' => $jenis->value,
            'berat' => fake()->randomFloat(2, 0.5, 20),
            'harga' => fake()->numberBetween(3, 50) * 1000,
            'foto_path' => null,
            'ai_label' => $jenis->label(),
            'ai_confidence' => fake()->randomFloat(2, 0.6, 0.99),
            'status' => ListingStatus::Tersedia->value,
            'lat' => -8.67 + fake()->randomFloat(4, -0.02, 0.02),
            'lng' => 115.21 + fake()->randomFloat(4, -0.02, 0.02),
        ];
    }
}
