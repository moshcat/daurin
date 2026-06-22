<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property float|null $lat
 * @property float|null $lng
 * @property string|null $nama_pt
 * @property string|null $alamat_pt
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'lat',
        'lng',
        'nama_pt',
        'alamat_pt',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'lat' => 'double',
            'lng' => 'double',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function listingSampah(): HasMany
    {
        return $this->hasMany(ListingSampah::class);
    }

    public function bahanBaku(): HasMany
    {
        return $this->hasMany(BahanBaku::class);
    }

    public function pengepulJenis(): HasMany
    {
        return $this->hasMany(PengepulJenis::class);
    }

    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'industri_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isRumahTangga(): bool
    {
        return $this->role?->value === UserRole::RumahTangga->value;
    }

    public function isPengepul(): bool
    {
        return $this->role?->value === UserRole::Pengepul->value;
    }

    public function isIndustri(): bool
    {
        return $this->role?->value === UserRole::Industri->value;
    }
}
