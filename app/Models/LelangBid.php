<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $lelang_id
 * @property int $industri_id
 * @property string $harga
 * @property bool $is_buyout
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class LelangBid extends Model
{
    /** @use HasFactory<\Database\Factories\LelangBidFactory> */
    use HasFactory;

    protected $table = 'lelang_bid';

    protected $fillable = [
        'lelang_id',
        'industri_id',
        'harga',
        'is_buyout',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'is_buyout' => 'boolean',
        ];
    }

    public function lelang(): BelongsTo
    {
        return $this->belongsTo(Lelang::class);
    }

    /** The industri who placed this bid. */
    public function industri(): BelongsTo
    {
        return $this->belongsTo(User::class, 'industri_id');
    }
}
