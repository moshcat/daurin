<?php

namespace Database\Factories;

use App\Enums\BahanBakuStatus;
use App\Enums\JenisSampah;
use App\Models\BahanBaku;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BahanBaku>
 */
class BahanBakuFactory extends Factory
{
    protected $model = BahanBaku::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement(JenisSampah::cases());

        return [
            'user_id' => User::factory()->pengepul(),
            'source_listing_id' => null,
            'jenis_sampah' => $jenis->value,
            'peruntukan' => fake()->randomElement([
                'Daur ulang kemasan',
                'Bahan baku biji plastik',
                'Pulp kertas',
                'Peleburan logam',
            ]),
            'berat' => fake()->randomFloat(2, 1, 50),
            'harga_awal' => fake()->numberBetween(15, 80) * 1000,
            'status' => BahanBakuStatus::Tersedia->value,
        ];
    }

    public function dilelang(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BahanBakuStatus::Dilelang->value,
        ]);
    }

    public function terjual(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BahanBakuStatus::Terjual->value,
        ]);
    }
}
