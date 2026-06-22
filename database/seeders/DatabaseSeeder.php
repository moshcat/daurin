<?php

namespace Database\Seeders;

use App\Enums\BahanBakuStatus;
use App\Enums\BahanJadiStatus;
use App\Enums\JenisSampah;
use App\Enums\LelangStatus;
use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Models\BahanBaku;
use App\Models\BahanJadi;
use App\Models\Lelang;
use App\Models\ListingSampah;
use App\Models\PengepulJenis;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed minimal: satu data valid per entitas + satu lelang aktif yang
     * langsung bisa didemokan secara real-time.
     *
     * Akun demo (password: daurin123):
     *   rt@daurin.test       — Rumah Tangga
     *   pengepul@daurin.test — Pengepul
     *   industri@daurin.test — Industri Pengolah
     */
    public function run(): void
    {
        // ── 1. Akun demo per peran ────────────────────────────────────────────
        $rt = User::firstOrCreate(
            ['email' => 'rt@daurin.test'],
            [
                'name' => 'Ibu Wayan (RT Demo)',
                'password' => Hash::make('daurin123'),
                'role' => UserRole::RumahTangga->value,
                'lat' => -8.6705,
                'lng' => 115.2126,
                'email_verified_at' => now(),
            ],
        );

        $pengepul = User::firstOrCreate(
            ['email' => 'pengepul@daurin.test'],
            [
                'name' => 'Pak Made (Pengepul Demo)',
                'password' => Hash::make('daurin123'),
                'role' => UserRole::Pengepul->value,
                'lat' => -8.6550,
                'lng' => 115.2200,
                'email_verified_at' => now(),
            ],
        );

        $industri = User::firstOrCreate(
            ['email' => 'industri@daurin.test'],
            [
                'name' => 'PT Recycle Bali (Industri Demo)',
                'password' => Hash::make('daurin123'),
                'role' => UserRole::Industri->value,
                'lat' => -8.7000,
                'lng' => 115.1800,
                'nama_pt' => 'PT Recycle Bali',
                'alamat_pt' => 'Jl. Recycle No. 123, Denpasar, Bali 80222',
                'email_verified_at' => now(),
            ],
        );

        // ── 2. Satu jenis sampah ditangani pengepul ───────────────────────────
        PengepulJenis::firstOrCreate([
            'user_id' => $pengepul->id,
            'jenis_sampah' => JenisSampah::Kardus->value,
        ]);

        // ── 3. Satu listing sampah dari RT (lapis 1, tersedia) ─────────────────
        ListingSampah::firstOrCreate(
            ['user_id' => $rt->id, 'jenis_sampah' => JenisSampah::Kardus->value, 'berat' => 8.0],
            [
                'harga' => 4000,
                'status' => ListingStatus::Tersedia->value,
                'ai_label' => 'Kardus',
                'ai_confidence' => 0.91,
                'lat' => -8.6705,
                'lng' => 115.2126,
            ],
        );

        // ── 4. Satu bahan baku pengepul (lapis 2), sedang dilelang ─────────────
        $bahanBaku = BahanBaku::firstOrCreate(
            ['user_id' => $pengepul->id, 'jenis_sampah' => JenisSampah::Kardus->value, 'berat' => 7.5],
            [
                'source_listing_id' => null,
                'peruntukan' => 'Daur ulang kertas kemasan',
                'harga_awal' => 35000,
                'status' => BahanBakuStatus::Dilelang->value,
            ],
        );

        // ── 5. Satu lelang berlangsung + satu tawaran awal dari industri ───────
        $lelang = Lelang::firstOrCreate(
            ['bahan_baku_id' => $bahanBaku->id],
            [
                'harga_awal' => 35000,
                'kelipatan' => 1000,
                'harga_buyout' => 60000,
                'status' => LelangStatus::Berlangsung->value,
                'waktu_mulai' => now(),
                'waktu_selesai' => now()->addHours(6),
            ],
        );

        if ($lelang->bids()->count() === 0) {
            $bid = $lelang->bids()->create([
                'industri_id' => $industri->id,
                'harga' => 36000,
                'is_buyout' => false,
            ]);
            $lelang->update(['highest_bid_id' => $bid->id]);
        }

        // ── 6. Satu bahan baku jadi dari industri (lapis 3) ────────────────────
        BahanJadi::firstOrCreate(
            ['user_id' => $industri->id, 'nama' => 'Lembaran Kardus Olahan'],
            [
                'source_pesanan_id' => null,
                'jenis_sampah' => JenisSampah::Kardus->value,
                'deskripsi' => 'Hasil olahan bahan baku kardus, siap pakai industri kemasan.',
                'berat' => 6.0,
                'harga' => 52000,
                'status' => BahanJadiStatus::Tersedia->value,
            ],
        );
    }
}
