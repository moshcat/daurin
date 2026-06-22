<?php

namespace App\Models;

use App\Enums\PesananStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pesanan extends Model
{
    protected $table = 'pesanan';

    protected $fillable = [
        'bahan_baku_id',
        'lelang_id',
        'industri_id',
        'status',
        'harga_sepakat',
        'dibayar_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PesananStatus::class,
            'harga_sepakat' => 'decimal:2',
            'dibayar_at' => 'datetime',
        ];
    }

    public function sudahDibayar(): bool
    {
        return $this->dibayar_at !== null;
    }

    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class);
    }

    /** The auction this settlement was produced from, if any. */
    public function lelang(): BelongsTo
    {
        return $this->belongsTo(Lelang::class);
    }

    /** The industri buyer. */
    public function industri(): BelongsTo
    {
        return $this->belongsTo(User::class, 'industri_id');
    }

    public function negosiasi(): HasMany
    {
        return $this->hasMany(Negosiasi::class);
    }

    /** The finished material produced from this deal, if any. */
    public function bahanJadi(): HasOne
    {
        return $this->hasOne(BahanJadi::class, 'source_pesanan_id');
    }
}
