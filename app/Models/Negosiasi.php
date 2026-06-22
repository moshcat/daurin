<?php

namespace App\Models;

use App\Enums\NegosiaPengirim;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Negosiasi extends Model
{
    protected $table = 'negosiasi';

    protected $fillable = [
        'pesanan_id',
        'pengirim',
        'harga',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'pengirim' => NegosiaPengirim::class,
            'harga' => 'decimal:2',
        ];
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }
}
