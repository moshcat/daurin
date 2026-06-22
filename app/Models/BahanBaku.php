<?php

namespace App\Models;

use App\Enums\BahanBakuStatus;
use App\Enums\JenisSampah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BahanBaku extends Model
{
    protected $table = 'bahan_baku';

    protected $fillable = [
        'user_id',
        'source_listing_id',
        'jenis_sampah',
        'peruntukan',
        'berat',
        'harga_awal',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'jenis_sampah' => JenisSampah::class,
            'status' => BahanBakuStatus::class,
            'berat' => 'decimal:2',
            'harga_awal' => 'decimal:2',
        ];
    }

    /** The pengepul who owns this bahan baku. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The original listing sampah this was sourced from. */
    public function sourceListing(): BelongsTo
    {
        return $this->belongsTo(ListingSampah::class, 'source_listing_id');
    }

    public function pesanan(): HasOne
    {
        return $this->hasOne(Pesanan::class);
    }
}
