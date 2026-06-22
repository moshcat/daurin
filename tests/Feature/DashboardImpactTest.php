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

class DashboardImpactTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_includes_impact_metrics(): void
    {
        $rt = User::factory()->create(['role' => UserRole::RumahTangga->value]);
        ListingSampah::create([
            'user_id' => $rt->id,
            'jenis_sampah' => JenisSampah::PlastikPet->value,
            'berat' => 10.0,
            'harga' => 7000,
            'status' => ListingStatus::Terjual->value,
        ]);

        $this->actingAs($rt)->get(route('dashboard'))->assertInertia(
            fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.kg_recycled', fn ($kg) => (float) $kg === 10.0)
                ->has('stats.co2_saved_kg')
                ->has('stats.nilai_ekonomi')
                ->has('stats.volume_per_jenis', 8)
        );
    }
}
