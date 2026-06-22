<?php

namespace Database\Seeders;

use App\Enums\BahanBakuStatus;
use App\Enums\BahanJadiStatus;
use App\Enums\JenisSampah;
use App\Enums\ListingStatus;
use App\Enums\NegosiaPengirim;
use App\Enums\PesananStatus;
use App\Enums\UserRole;
use App\Models\BahanBaku;
use App\Models\BahanJadi;
use App\Models\ListingSampah;
use App\Models\Negosiasi;
use App\Models\PengepulJenis;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed demo accounts and sample data for all 3 roles.
     * Demo credentials (all pw: daurin123):
     *   rt@daurin.test       — Rumah Tangga
     *   pengepul@daurin.test — Pengepul
     *   industri@daurin.test — Industri Pengolah
     */
    public function run(): void
    {
        // ── 1. Demo accounts ──────────────────────────────────────────────────

        /** @var User $rt */
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

        /** @var User $pengepul */
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

        /** @var User $industri */
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

        // ── 2. Jenis sampah ditangani pengepul ────────────────────────────────

        $handledJenis = [
            JenisSampah::PlastikPet->value,
            JenisSampah::PlastikHdpe->value,
            JenisSampah::Kertas->value,
            JenisSampah::Kardus->value,
        ];

        foreach ($handledJenis as $jenis) {
            PengepulJenis::firstOrCreate([
                'user_id' => $pengepul->id,
                'jenis_sampah' => $jenis,
            ]);
        }

        // ── 3. Listing sampah dari RT (Denpasar coords) ───────────────────────

        $listingData = [
            [
                'jenis_sampah' => JenisSampah::PlastikPet->value,
                'berat' => 3.5,
                'harga' => 7000,
                'status' => ListingStatus::Tersedia->value,
                'ai_label' => 'Plastik PET',
                'ai_confidence' => 0.92,
                'lat' => -8.6705,
                'lng' => 115.2126,
            ],
            [
                'jenis_sampah' => JenisSampah::Kertas->value,
                'berat' => 5.0,
                'harga' => 5000,
                'status' => ListingStatus::Tersedia->value,
                'ai_label' => 'Kertas',
                'ai_confidence' => 0.88,
                'lat' => -8.6720,
                'lng' => 115.2100,
            ],
            [
                'jenis_sampah' => JenisSampah::PlastikHdpe->value,
                'berat' => 2.0,
                'harga' => 6000,
                'status' => ListingStatus::Diambil->value,
                'lat' => -8.6680,
                'lng' => 115.2150,
                'claimed_by' => $pengepul->id,
            ],
            [
                'jenis_sampah' => JenisSampah::Kardus->value,
                'berat' => 8.0,
                'harga' => 4000,
                'status' => ListingStatus::Terjual->value,
                'lat' => -8.6740,
                'lng' => 115.2080,
                'claimed_by' => $pengepul->id,
            ],
            [
                'jenis_sampah' => JenisSampah::PlastikPet->value,
                'berat' => 1.5,
                'harga' => 4500,
                'status' => ListingStatus::Tersedia->value,
                'ai_label' => 'Plastik PET',
                'ai_confidence' => 0.79,
                'lat' => -8.6690,
                'lng' => 115.2140,
            ],
        ];

        $createdListings = [];
        foreach ($listingData as $data) {
            $listing = ListingSampah::firstOrCreate(
                ['user_id' => $rt->id, 'jenis_sampah' => $data['jenis_sampah'], 'berat' => $data['berat']],
                array_merge($data, ['user_id' => $rt->id]),
            );
            $createdListings[] = $listing;
        }

        // Bulk demo listings so the marketplace grid has enough rows to infinite-scroll.
        ListingSampah::factory()->count(48)->create(['user_id' => $rt->id]);

        // ── 4. Bahan baku dari pengepul (dengan source_listing_id) ────────────

        // Find the "terjual" listing (kardus) to use as source
        $sourceListing = collect($createdListings)->firstWhere('status', ListingStatus::Terjual->value);

        $bb1 = BahanBaku::firstOrCreate(
            ['user_id' => $pengepul->id, 'source_listing_id' => $sourceListing?->id ?? null, 'jenis_sampah' => JenisSampah::Kardus->value],
            [
                'user_id' => $pengepul->id,
                'source_listing_id' => $sourceListing?->id,
                'jenis_sampah' => JenisSampah::Kardus->value,
                'peruntukan' => 'Daur ulang kertas kemasan',
                'berat' => 7.5,
                'harga_awal' => 35000,
                'status' => BahanBakuStatus::Tersedia->value,
            ],
        );

        $petListing = collect($createdListings)->firstWhere('jenis_sampah', JenisSampah::PlastikPet);

        $bb2 = BahanBaku::firstOrCreate(
            ['user_id' => $pengepul->id, 'jenis_sampah' => JenisSampah::PlastikPet->value, 'berat' => 4.0],
            [
                'user_id' => $pengepul->id,
                'source_listing_id' => $petListing?->id,
                'jenis_sampah' => JenisSampah::PlastikPet->value,
                'peruntukan' => 'Bahan baku botol PET daur ulang',
                'berat' => 4.0,
                'harga_awal' => 28000,
                'status' => BahanBakuStatus::Terjual->value,
            ],
        );

        // ── 5. Pesanan + riwayat negosiasi ────────────────────────────────────

        $pesanan = Pesanan::firstOrCreate(
            ['bahan_baku_id' => $bb2->id, 'industri_id' => $industri->id],
            [
                'bahan_baku_id' => $bb2->id,
                'industri_id' => $industri->id,
                'status' => PesananStatus::Deal->value,
                'harga_sepakat' => 26000,
            ],
        );

        if ($pesanan->negosiasi()->count() === 0) {
            Negosiasi::create([
                'pesanan_id' => $pesanan->id,
                'pengirim' => NegosiaPengirim::Industri->value,
                'harga' => 24000,
                'catatan' => 'Penawaran awal kami',
            ]);
            Negosiasi::create([
                'pesanan_id' => $pesanan->id,
                'pengirim' => NegosiaPengirim::Pengepul->value,
                'harga' => 27000,
                'catatan' => 'Harga minimal kami',
            ]);
            Negosiasi::create([
                'pesanan_id' => $pesanan->id,
                'pengirim' => NegosiaPengirim::Industri->value,
                'harga' => 26000,
                'catatan' => 'Setuju 26.000',
            ]);
        }

        // Pesanan aktif dalam negosiasi
        $pesananAktif = Pesanan::firstOrCreate(
            ['bahan_baku_id' => $bb1->id, 'industri_id' => $industri->id],
            [
                'bahan_baku_id' => $bb1->id,
                'industri_id' => $industri->id,
                'status' => PesananStatus::Nego->value,
                'harga_sepakat' => null,
            ],
        );

        if ($pesananAktif->negosiasi()->count() === 0) {
            Negosiasi::create([
                'pesanan_id' => $pesananAktif->id,
                'pengirim' => NegosiaPengirim::Industri->value,
                'harga' => 30000,
                'catatan' => 'Kami tertarik dengan kardus Anda',
            ]);
        }

        // ── 6. Bahan baku jadi dari industri (lapis ke-3) ─────────────────────

        BahanJadi::firstOrCreate(
            ['user_id' => $industri->id, 'source_pesanan_id' => $pesanan->id],
            [
                'user_id' => $industri->id,
                'source_pesanan_id' => $pesanan->id,
                'nama' => 'Biji Plastik PET Daur Ulang',
                'jenis_sampah' => JenisSampah::PlastikPet->value,
                'deskripsi' => 'Hasil olahan bahan baku PET, siap pakai industri manufaktur.',
                'berat' => 3.8,
                'harga' => 52000,
                'status' => BahanJadiStatus::Tersedia->value,
            ],
        );

        // Extra demo finished goods so the third etalase layer is populated.
        BahanJadi::factory()->count(6)->create(['user_id' => $industri->id]);
    }
}
