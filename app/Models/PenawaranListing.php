<?php

namespace App\Models;

use App\Enums\PenawaranStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Penawaran harga dari pengepul untuk listing sampah RT (tawar-menawar lapis RT↔pengepul).
 *
 * @property int $id
 * @property int $listing_sampah_id
 * @property int $pengepul_id
 * @property string $harga
 * @property PenawaranStatus $status
 * @property Carbon|null $created_at
 */
class PenawaranListing extends Model
{
    protected $table = 'penawaran_listing';

    protected $fillable = [
        'listing_sampah_id',
        'pengepul_id',
        'harga',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'status' => PenawaranStatus::class,
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ListingSampah::class, 'listing_sampah_id');
    }

    /** The pengepul who made the offer. */
    public function pengepul(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengepul_id');
    }
}
