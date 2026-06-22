<?php

namespace Tests\Feature;

use App\Enums\JenisSampah;
use App\Enums\ListingStatus;
use App\Models\ListingSampah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_edit_page_for_available_listing(): void
    {
        $rt = User::factory()->rumahTangga()->create();
        $listing = ListingSampah::factory()->for($rt)->create();

        $this->actingAs($rt)
            ->get("/rt/{$listing->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rt/Edit')
                ->where('listing.id', $listing->id)
                ->has('jenisSampahOptions')
            );
    }

    public function test_owner_can_update_available_listing(): void
    {
        $rt = User::factory()->rumahTangga()->create();
        $listing = ListingSampah::factory()->for($rt)->create([
            'jenis_sampah' => JenisSampah::Kertas->value,
            'berat' => 3,
            'harga' => 5000,
        ]);

        $this->actingAs($rt)
            ->put("/rt/{$listing->id}", [
                'jenis_sampah' => JenisSampah::Logam->value,
                'berat' => 8,
                'harga' => 12000,
            ])
            ->assertRedirect(route('rt.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('listing_sampah', [
            'id' => $listing->id,
            'jenis_sampah' => JenisSampah::Logam->value,
            'berat' => 8,
            'harga' => 12000,
        ]);
    }

    public function test_owner_cannot_update_claimed_listing(): void
    {
        $rt = User::factory()->rumahTangga()->create();
        $listing = ListingSampah::factory()->for($rt)->create([
            'status' => ListingStatus::Diambil->value,
            'harga' => 5000,
        ]);

        $this->actingAs($rt)
            ->put("/rt/{$listing->id}", [
                'jenis_sampah' => $listing->jenis_sampah->value,
                'berat' => 8,
                'harga' => 99000,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('listing_sampah', [
            'id' => $listing->id,
            'harga' => 5000,
        ]);
    }

    public function test_user_cannot_update_another_users_listing(): void
    {
        $owner = User::factory()->rumahTangga()->create();
        $other = User::factory()->rumahTangga()->create();
        $listing = ListingSampah::factory()->for($owner)->create(['harga' => 5000]);

        $this->actingAs($other)
            ->put("/rt/{$listing->id}", [
                'jenis_sampah' => $listing->jenis_sampah->value,
                'berat' => 8,
                'harga' => 99000,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('listing_sampah', [
            'id' => $listing->id,
            'harga' => 5000,
        ]);
    }

    public function test_update_rejects_weight_below_one_kilogram(): void
    {
        $rt = User::factory()->rumahTangga()->create();
        $listing = ListingSampah::factory()->for($rt)->create();

        $this->actingAs($rt)
            ->put("/rt/{$listing->id}", [
                'jenis_sampah' => $listing->jenis_sampah->value,
                'berat' => 0.5,
                'harga' => 5000,
            ])
            ->assertSessionHasErrors('berat');
    }
}
