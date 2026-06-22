<?php

namespace App\Events;

use App\Models\Lelang;
use App\Models\LelangBid;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Broadcast saat ada tawaran baru pada sebuah lelang.
 * ShouldBroadcastNow → dikirim sinkron (tanpa perlu queue worker) demi latensi rendah.
 */
class BidPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Lelang $lelang, public LelangBid $bid) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel('lelang.'.$this->lelang->id)];
    }

    public function broadcastAs(): string
    {
        return 'BidPlaced';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->bid->loadMissing('industri');

        return [
            'bid_id' => $this->bid->id,
            'industri_nama' => Str::mask($this->bid->industri->name, '*', 4),
            'harga' => (float) $this->bid->harga,
            'harga_tertinggi' => (float) $this->lelang->hargaTertinggi(),
            'jumlah_bid' => $this->lelang->bids()->count(),
            'is_buyout' => $this->bid->is_buyout,
            'waktu_selesai' => $this->lelang->waktu_selesai->toIso8601String(),
            'created_at' => $this->bid->created_at->toIso8601String(),
        ];
    }
}
