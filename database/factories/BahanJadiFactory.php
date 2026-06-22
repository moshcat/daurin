<?php

namespace Database\Factories;

use App\Enums\BahanJadiStatus;
use App\Enums\JenisSampah;
use App\Models\BahanJadi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BahanJadi>
 */
class BahanJadiFactory extends Factory
{
    protected $model = BahanJadi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement(JenisSampah::cases());
        $produk = [
            'plastik_pet'  => 'Pelet PET Daur Ulang',
            'plastik_hdpe' => 'Biji Plastik HDPE',
            'kertas'       => 'Bubur Kertas Daur Ulang',
            'kardus'       => 'Lembaran Kardus Olahan',
            'logam'        => 'Ingot Logam Daur Ulang',
            'kaleng'       => 'Serpih Aluminium',
            'kaca'         => 'Cullet Kaca',
            'elektronik'   => 'Komponen E-waste Terpilah',
        ];

        return [
            'user_id' => User::factory(),
            'source_pesanan_id' => null,
            'nama' => $produk[$jenis->value] ?? 'Bahan Baku Jadi',
            'jenis_sampah' => $jenis->value,
            'deskripsi' => fake()->sentence(),
            'berat' => fake()->randomFloat(2, 1, 30),
            'harga' => fake()->numberBetween(20, 120) * 1000,
            'foto_path' => null,
            'status' => BahanJadiStatus::Tersedia->value,
        ];
    }
}
