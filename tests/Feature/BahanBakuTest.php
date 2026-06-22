<?php

namespace Tests\Feature;

use App\Enums\JenisSampah;
use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Models\ListingSampah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BahanBakuTest extends TestCase
{
    use RefreshDatabase;

    private function claimedListing(User $pengepul, float $berat): ListingSampah
    {
        $rt = User::factory()->create(['role' => UserRole::RumahTangga->value]);

        return ListingSampah::create([
            'user_id' => $rt->id,
            'jenis_sampah' => JenisSampah::Kardus->value,
            'berat' => $berat,
            'harga' => 5000,
            'status' => ListingStatus::Diambil->value,
            'claimed_by' => $pengepul->id,
        ]);
    }

    public function test_pengepul_can_convert_small_claimed_listing_into_bahan_baku(): void
    {
        // Regression: a sub-10kg listing must still be convertible (previously
        // blocked by a hard `min:10` floor that contradicted the 1 kg listing minimum).
        $pengepul = User::factory()->create(['role' => UserRole::Pengepul->value]);
        $listing = $this->claimedListing($pengepul, 2.2);

        $this->actingAs($pengepul)
            ->post("/pengepul/bahan-baku/{$listing->id}", [
                'jenis_sampah' => JenisSampah::Kardus->value,
                'peruntukan' => 'daur ulang kardus',
                'berat' => 2.2,
                'harga_awal' => 3000,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bahan_baku', [
            'source_listing_id' => $listing->id,
            'user_id' => $pengepul->id,
        ]);
        $this->assertSame(ListingStatus::Terjual, $listing->fresh()->status);
    }

    public function test_berat_equal_to_source_listing_weight_is_allowed(): void
    {
        $pengepul = User::factory()->create(['role' => UserRole::Pengepul->value]);
        $listing = $this->claimedListing($pengepul, 8.0);

        $this->actingAs($pengepul)
            ->post("/pengepul/bahan-baku/{$listing->id}", [
                'jenis_sampah' => JenisSampah::Kardus->value,
                'berat' => 8.0,
                'harga_awal' => 3000,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bahan_baku', ['source_listing_id' => $listing->id]);
    }

    public function test_berat_cannot_exceed_source_listing_weight(): void
    {
        $pengepul = User::factory()->create(['role' => UserRole::Pengepul->value]);
        $listing = $this->claimedListing($pengepul, 8.0);

        $this->actingAs($pengepul)
            ->post("/pengepul/bahan-baku/{$listing->id}", [
                'jenis_sampah' => JenisSampah::Kardus->value,
                'berat' => 12.0,
                'harga_awal' => 3000,
            ])
            ->assertSessionHasErrors('berat');

        $this->assertDatabaseCount('bahan_baku', 0);
        $this->assertSame(ListingStatus::Diambil, $listing->fresh()->status);
    }

    public function test_berat_must_be_greater_than_zero(): void
    {
        $pengepul = User::factory()->create(['role' => UserRole::Pengepul->value]);
        $listing = $this->claimedListing($pengepul, 8.0);

        $this->actingAs($pengepul)
            ->post("/pengepul/bahan-baku/{$listing->id}", [
                'jenis_sampah' => JenisSampah::Kardus->value,
                'berat' => 0,
                'harga_awal' => 3000,
            ])
            ->assertSessionHasErrors('berat');

        $this->assertDatabaseCount('bahan_baku', 0);
    }
}
