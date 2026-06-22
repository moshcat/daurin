<?php

namespace Tests\Feature;

use App\Enums\JenisSampah;
use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Models\ListingSampah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_exposes_three_etalase_layers(): void
    {
        $rt = User::factory()->create(['role' => UserRole::RumahTangga->value]);
        ListingSampah::create([
            'user_id' => $rt->id,
            'jenis_sampah' => JenisSampah::PlastikPet->value,
            'berat' => 3.0,
            'harga' => 7000,
            'status' => ListingStatus::Tersedia->value,
        ]);

        $this->get('/')->assertInertia(
            fn (Assert $page) => $page
                ->component('Welcome')
                ->has('listings')
                ->has('bahanBaku')
                ->has('bahanJadi')
                ->has('stats.listingCount')
                ->has('stats.bahanBakuCount')
                ->has('stats.bahanJadiCount')
        );
    }
}
