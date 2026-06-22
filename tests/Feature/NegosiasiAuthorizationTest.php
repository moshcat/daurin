<?php

namespace Tests\Feature;

use App\Enums\BahanBakuStatus;
use App\Enums\JenisSampah;
use App\Enums\PesananStatus;
use App\Enums\UserRole;
use App\Models\BahanBaku;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NegosiasiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makePesanan(User $industri, User $pengepul): Pesanan
    {
        $bahanBaku = BahanBaku::create([
            'user_id' => $pengepul->id,
            'jenis_sampah' => JenisSampah::Kardus->value,
            'berat' => 5.0,
            'harga_awal' => 30000,
            'status' => BahanBakuStatus::Tersedia->value,
        ]);

        return Pesanan::create([
            'bahan_baku_id' => $bahanBaku->id,
            'industri_id' => $industri->id,
            'status' => PesananStatus::Nego->value,
        ]);
    }

    public function test_industri_cannot_view_another_industris_pesanan(): void
    {
        $pengepul = User::factory()->create(['role' => UserRole::Pengepul->value]);
        $owner = User::factory()->create(['role' => UserRole::Industri->value]);
        $intruder = User::factory()->create(['role' => UserRole::Industri->value]);
        $pesanan = $this->makePesanan($owner, $pengepul);

        $this->actingAs($intruder)
            ->get("/industri/pesanan/{$pesanan->id}")
            ->assertForbidden();
    }

    public function test_industri_cannot_offer_on_another_industris_pesanan(): void
    {
        $pengepul = User::factory()->create(['role' => UserRole::Pengepul->value]);
        $owner = User::factory()->create(['role' => UserRole::Industri->value]);
        $intruder = User::factory()->create(['role' => UserRole::Industri->value]);
        $pesanan = $this->makePesanan($owner, $pengepul);

        $this->actingAs($intruder)
            ->post("/industri/pesanan/{$pesanan->id}/tawar", ['harga' => 25000])
            ->assertForbidden();
    }

    public function test_pengepul_cannot_view_another_pengepuls_pesanan(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Pengepul->value]);
        $intruder = User::factory()->create(['role' => UserRole::Pengepul->value]);
        $industri = User::factory()->create(['role' => UserRole::Industri->value]);
        $pesanan = $this->makePesanan($industri, $owner);

        $this->actingAs($intruder)
            ->get("/pengepul/pesanan/{$pesanan->id}")
            ->assertForbidden();
    }
}
