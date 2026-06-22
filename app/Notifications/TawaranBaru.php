<?php

namespace App\Notifications;

use App\Models\Lelang;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Ke Pengepul (penjual): ada penawaran harga baru pada barangnya.
 */
class TawaranBaru extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lelang $lelang,
        public float $harga,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->lelang->loadMissing('bahanBaku');
        $jenis = $this->lelang->bahanBaku->jenis_sampah->label();

        return (new MailMessage)
            ->subject('Penawaran Harga Baru — Daurin')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Ada penawaran baru sebesar Rp '.number_format($this->harga, 0, ',', '.').' untuk '.$jenis.' Anda.')
            ->line('Tawaran tertinggi saat ini: Rp '.number_format((float) ($this->lelang->hargaTertinggi() ?? $this->harga), 0, ',', '.').'.')
            ->action('Pantau Negosiasi', url('/lelang/'.$this->lelang->id))
            ->salutation('Salam hijau, Tim Daurin ♻️');
    }
}
