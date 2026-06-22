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

class BahanJadiTest extends TestCase
{
    use RefreshDatabase;

    private function makeDeal(User $industri, User $pengepul, string $status = PesananStatus::Deal->value): Pesanan
    {
        $bahanBaku = BahanBaku::create([
            'user_id' => $pengepul->id,
            'jenis_sampah' => JenisSampah::PlastikPet->value,
            'berat' => 4.0,
            'harga_awal' => 28000,
            'status' => BahanBakuStatus::Terjual->value,
        ]);

        return Pesanan::create([
            'bahan_baku_id' => $bahanBaku->id,
            'industri_id' => $industri->id,
            'status' => $status,
            'harga_sepakat' => $status === PesananStatus::Deal->value ? 26000 : null,
        ]);
    }

    public function test_industri_can_process_a_deal_into_bahan_jadi(): void
    {
        $pengepul = User::factory()->create(['role' => UserRole::Pengepul->value]);
        $industri = User::factory()->create(['role' => UserRole::Industri->value]);
        $pesanan = $this->makeDeal($industri, $pengepul);

        $this->actingAs($industri)
            ->post('/industri/bahan-jadi', [
                'source_pesanan_id' => $pesanan->id,
                'nama' => 'Pelet PET Daur Ulang',
                'jenis_sampah' => JenisSampah::PlastikPet->value,
                'berat' => 3.5,
                'harga' => 52000,
            ])
            ->assertRedirect(route('industri.bahanjadi.index'));

        $this->assertDatabaseHas('bahan_jadi', [
            'source_pesanan_id' => $pesanan->id,
            'user_id' => $industri->id,
            'nama' => 'Pelet PET Daur Ulang',
        ]);
    }

    public function test_cannot_process_a_pesanan_that_is_not_deal(): void
    {
        $pengepul = User::factory()->create(['role' => UserRole::Pengepul->value]);
        $industri = User::factory()->create(['role' => UserRole::Industri->value]);
        $pesanan = $this->makeDeal($industri, $pengepul, PesananStatus::Nego->value);

        $this->actingAs($industri)
            ->post('/industri/bahan-jadi', [
                'source_pesanan_id' => $pesanan->id,
                'nama' => 'Produk',
                'jenis_sampah' => JenisSampah::PlastikPet->value,
                'berat' => 3.5,
                'harga' => 52000,
            ])
            ->assertSessionHasErrors('pesanan');

        $this->assertDatabaseMissing('bahan_jadi', ['source_pesanan_id' => $pesanan->id]);
    }

    public function test_cannot_process_a_pesanan_owned_by_another_industri(): void
    {
        $pengepul = User::factory()->create(['role' => UserRole::Pengepul->value]);
        $ownerIndustri = User::factory()->create(['role' => UserRole::Industri->value]);
        $otherIndustri = User::factory()->create(['role' => UserRole::Industri->value]);
        $pesanan = $this->makeDeal($ownerIndustri, $pengepul);

        $this->actingAs($otherIndustri)
            ->post('/industri/bahan-jadi', [
                'source_pesanan_id' => $pesanan->id,
                'nama' => 'Produk',
                'jenis_sampah' => JenisSampah::PlastikPet->value,
                'berat' => 3.5,
                'harga' => 52000,
            ])
            ->assertSessionHasErrors('pesanan');

        $this->assertDatabaseMissing('bahan_jadi', ['source_pesanan_id' => $pesanan->id]);
    }
}
