<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_industri_registration_stores_company_fields()
    {
        $this->post(route('register.store'), [
            'name' => 'Budi Industri',
            'email' => 'budi@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'industri',
            'nama_pt' => 'PT Daur Maju',
            'alamat_pt' => 'Jl. Industri No. 1, Denpasar',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'role' => 'industri',
            'nama_pt' => 'PT Daur Maju',
            'alamat_pt' => 'Jl. Industri No. 1, Denpasar',
        ]);
    }

    public function test_industri_registration_requires_company_fields()
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Budi Industri',
            'email' => 'budi2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'industri',
        ]);

        $response->assertSessionHasErrors(['nama_pt', 'alamat_pt']);
        $this->assertGuest();
    }
}
