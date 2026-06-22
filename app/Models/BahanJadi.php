<?php

namespace App\Models;

use App\Enums\BahanJadiStatus;
use App\Enums\JenisSampah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BahanJadi extends Model
{
    /** @use HasFactory<\Database\Factories\BahanJadiFactory> */
    use HasFactory;

    protected $table = 'bahan_jadi';

    protected $fillable = [
        'user_id',
        'source_pesanan_id',
        'nama',
        'jenis_sampah',
        'deskripsi',
        'berat',
        'harga',
        'foto_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'jenis_sampah' => JenisSampah::class,
            'status' => BahanJadiStatus::class,
            'berat' => 'decimal:2',
            'harga' => 'decimal:2',
        ];
    }

    /** The industri owner that processed this finished material. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The deal (pesanan) this finished material was produced from. */
    public function sourcePesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'source_pesanan_id');
    }
}
