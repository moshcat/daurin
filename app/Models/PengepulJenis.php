<?php

namespace App\Models;

use App\Enums\JenisSampah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengepulJenis extends Model
{
    protected $table = 'pengepul_jenis';

    protected $fillable = [
        'user_id',
        'jenis_sampah',
    ];

    protected function casts(): array
    {
        return [
            'jenis_sampah' => JenisSampah::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
