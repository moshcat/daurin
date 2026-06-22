<?php

namespace App\Events;

use App\Models\Lelang;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Broadcast saat lelang ditutup (menang / gagal / batal).
 */
class LelangClosed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Lelang $lelang) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel('lelang.'.$this->lelang->id)];
    }

    public function broadcastAs(): string
    {
        return 'LelangClosed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->lelang->loadMissing('pemenang');

        return [
            'status' => $this->lelang->status->value,
            'pemenang_nama' => $this->lelang->pemenang
                ? Str::mask($this->lelang->pemenang->name, '*', 4)
                : null,
            'harga_final' => $this->lelang->harga_final !== null ? (float) $this->lelang->harga_final : null,
            'pesanan_id' => $this->lelang->pesanan_id,
        ];
    }
}
