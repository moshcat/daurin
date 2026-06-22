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
        'industri_id',
        'status',
        'harga_sepakat',
    ];

    protected function casts(): array
    {
        return [
            'status' => PesananStatus::class,
            'harga_sepakat' => 'decimal:2',
        ];
    }

    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class);
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
