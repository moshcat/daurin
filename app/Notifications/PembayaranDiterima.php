<?php

namespace App\Notifications;

use App\Models\Pesanan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Ke Pengepul (penjual): pembayaran pesanan diterima (simulasi).
 */
class PembayaranDiterima extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Pesanan $pesanan) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->pesanan->loadMissing(['bahanBaku', 'industri']);
        $jenis = $this->pesanan->bahanBaku->jenis_sampah->label();

        return (new MailMessage)
            ->subject('Pembayaran Diterima — Daurin')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Pembayaran sebesar Rp '.number_format((float) $this->pesanan->harga_sepakat, 0, ',', '.').' untuk '.$jenis.' telah diterima.')
            ->line('Pembeli: '.($this->pesanan->industri?->name ?? 'Industri').'.')
            ->line('Pesanan kini berstatus LUNAS.')
            ->action('Lihat Pesanan', url('/dashboard'))
            ->salutation('Salam hijau, Tim Daurin ♻️');
    }
}
