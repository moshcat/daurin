<?php

namespace App\Models;

use App\Enums\JenisSampah;
use App\Enums\ListingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListingSampah extends Model
{
    /** @use HasFactory<\Database\Factories\ListingSampahFactory> */
    use HasFactory;

    protected $table = 'listing_sampah';

    protected $fillable = [
        'user_id',
        'jenis_sampah',
        'berat',
        'harga',
        'foto_path',
        'ai_label',
        'ai_confidence',
        'status',
        'claimed_by',
        'lat',
        'lng',
    ];

    protected function casts(): array
    {
        return [
            'jenis_sampah' => JenisSampah::class,
            'status' => ListingStatus::class,
            'berat' => 'decimal:2',
            'harga' => 'decimal:2',
            'ai_confidence' => 'double',
            'lat' => 'double',
            'lng' => 'double',
        ];
    }

    /** The rumah tangga owner. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Alias for clarity. */
    public function rt(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The pengepul who claimed the listing. */
    public function claimer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    /** Bahan baku entries derived from this listing. */
    public function bahanBaku(): HasMany
    {
        return $this->hasMany(BahanBaku::class, 'source_listing_id');
    }
}
