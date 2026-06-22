<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClassifyTest extends TestCase
{
    use RefreshDatabase;

    private function makeAuthUser(): User
    {
        return User::factory()->create(['role' => 'rumah_tangga']);
    }

    /** Fake the classifier API with a known successful response. */
    private function fakeApiSuccess(string $label = 'plastic', string $labelId = 'Plastik', float $confidence = 0.92): void
    {
        Http::fake([
            'anggapuspa-apisampahplastik.hf.space/*' => Http::response([
                'success' => true,
                'top'     => [
                    'label'      => $label,
                    'label_id'   => $labelId,
                    'emoji'      => '♻️',
                    'confidence' => $confidence,
                    'tip'        => 'Pisahkan berdasarkan jenis plastik.',
                ],
                'predictions'        => [],
                'processing_time_ms' => 120,
            ], 200),
        ]);
    }

    public function test_classify_returns_mapped_jenis_for_plastic(): void
    {
        Storage::fake('local');
        $this->fakeApiSuccess('plastic', 'Plastik', 0.92);

        $user = $this->makeAuthUser();
        $file = UploadedFile::fake()->image('botol.jpg');

        $response = $this->actingAs($user)
            ->postJson('/classify', ['file' => $file]);

        $response->assertOk()
            ->assertJson([
                'label'        => 'Plastik',
                'jenis_sampah' => 'plastik_pet',
                'supported'    => true,
            ]);

        $this->assertEqualsWithDelta(0.92, $response->json('confidence'), 0.01);
    }

    public function test_classify_maps_all_glass_variants_to_kaca(): void
    {
        foreach (['brown-glass', 'green-glass', 'white-glass'] as $glassLabel) {
            Storage::fake('local');
            $this->fakeApiSuccess($glassLabel, 'Kaca', 0.85);

            $user = $this->makeAuthUser();
            $file = UploadedFile::fake()->image('kaca.jpg');

            $response = $this->actingAs($user)
                ->postJson('/classify', ['file' => $file]);

            $response->assertOk()
                ->assertJson([
                    'jenis_sampah' => 'kaca',
                    'supported'    => true,
                ]);
        }
    }

    public function test_classify_returns_unsupported_for_unmapped_labels(): void
    {
        Storage::fake('local');
        $this->fakeApiSuccess('biological', 'Sampah Organik', 0.78);

        $user = $this->makeAuthUser();
        $file = UploadedFile::fake()->image('kompos.jpg');

        $response = $this->actingAs($user)
            ->postJson('/classify', ['file' => $file]);

        $response->assertOk()
            ->assertJson([
                'label'        => 'Sampah Organik',
                'jenis_sampah' => null,
                'supported'    => false,
            ]);
    }

    public function test_classify_falls_back_to_mock_when_api_fails(): void
    {
        Storage::fake('local');
        Http::fake([
            'anggapuspa-apisampahplastik.hf.space/*' => Http::response([], 500),
        ]);

        $user = $this->makeAuthUser();
        $file = UploadedFile::fake()->image('plastik_botol.jpg');

        $response = $this->actingAs($user)
            ->postJson('/classify', ['file' => $file]);

        $response->assertOk();
        $this->assertNotNull($response->json('jenis_sampah'));
        $this->assertTrue($response->json('supported'));
        $this->assertGreaterThan(0, $response->json('confidence'));
    }

    public function test_classify_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('sampah.jpg');

        // Web routes redirect unauthenticated requests to /login (302)
        $this->post('/classify', ['file' => $file])
            ->assertRedirect(route('login'));
    }

    public function test_classify_battery_maps_to_elektronik(): void
    {
        Storage::fake('local');
        $this->fakeApiSuccess('battery', 'Baterai', 0.88);

        $user = $this->makeAuthUser();
        $file = UploadedFile::fake()->image('baterai.jpg');

        $response = $this->actingAs($user)
            ->postJson('/classify', ['file' => $file]);

        $response->assertOk()
            ->assertJson([
                'jenis_sampah' => 'elektronik',
                'supported'    => true,
            ]);
    }
}
