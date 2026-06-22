<?php

namespace App\Models;

use App\Enums\LelangStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $bahan_baku_id
 * @property string $harga_awal
 * @property string $kelipatan
 * @property string|null $harga_reserve
 * @property string|null $harga_buyout
 * @property LelangStatus $status
 * @property Carbon $waktu_mulai
 * @property Carbon $waktu_selesai
 * @property int|null $highest_bid_id
 * @property int|null $pemenang_id
 * @property string|null $harga_final
 * @property int|null $pesanan_id
 */
class Lelang extends Model
{
    /** @use HasFactory<\Database\Factories\LelangFactory> */
    use HasFactory;

    protected $table = 'lelang';

    protected $fillable = [
        'bahan_baku_id',
        'harga_awal',
        'kelipatan',
        'harga_reserve',
        'harga_buyout',
        'status',
        'waktu_mulai',
        'waktu_selesai',
        'highest_bid_id',
        'pemenang_id',
        'harga_final',
        'pesanan_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => LelangStatus::class,
            'harga_awal' => 'decimal:2',
            'kelipatan' => 'decimal:2',
            'harga_reserve' => 'decimal:2',
            'harga_buyout' => 'decimal:2',
            'harga_final' => 'decimal:2',
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    /** The bahan baku being auctioned. */
    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(LelangBid::class);
    }

    /** Denormalised pointer to the current leading bid. */
    public function highestBid(): BelongsTo
    {
        return $this->belongsTo(LelangBid::class, 'highest_bid_id');
    }

    /** The winning industri, set when closed. */
    public function pemenang(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemenang_id');
    }

    /** The settlement order created when the auction is won. */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** Current highest price, or null when there are no bids yet. */
    public function hargaTertinggi(): ?string
    {
        return $this->highestBid?->harga;
    }

    /** Minimum acceptable next bid. */
    public function minimalBidBerikutnya(): string
    {
        $tertinggi = $this->hargaTertinggi();

        return $tertinggi === null
            ? (string) $this->harga_awal
            : bcadd($tertinggi, (string) $this->kelipatan, 2);
    }

    public function isOpen(): bool
    {
        return $this->status === LelangStatus::Berlangsung
            && $this->waktu_selesai->isFuture();
    }

    /** Auctions that are still open but past their deadline (for the closing job). */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', LelangStatus::Berlangsung->value)
            ->where('waktu_selesai', '<=', now());
    }

    public function scopeBerlangsung(Builder $query): Builder
    {
        return $query->where('status', LelangStatus::Berlangsung->value);
    }
}
