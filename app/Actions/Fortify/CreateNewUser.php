<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $roleValues = array_column(UserRole::cases(), 'value');
        $role = $input['role'] ?? UserRole::RumahTangga->value;

        Validator::make($input, [
            ...$this->profileRules(),
            ...$this->companyRules($role),
            'password' => $this->passwordRules(),
            'role' => ['nullable', 'string', 'in:'.implode(',', $roleValues)],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => $role,
            'lat' => $input['lat'] ?? null,
            'lng' => $input['lng'] ?? null,
            'nama_pt' => $input['nama_pt'] ?? null,
            'alamat_pt' => $input['alamat_pt'] ?? null,
        ]);
    }
}
