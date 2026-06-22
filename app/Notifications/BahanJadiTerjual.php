<?php

namespace App\Notifications;

use App\Models\BahanJadi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Ke Industri penjual: bahan baku jadinya terbeli & dibayar.
 */
class BahanJadiTerjual extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BahanJadi $bahanJadi) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->bahanJadi->loadMissing('pembeli');

        return (new MailMessage)
            ->subject('Bahan Baku Jadi Terjual — Daurin')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('"'.$this->bahanJadi->nama.'" terjual seharga Rp '.number_format((float) $this->bahanJadi->harga, 0, ',', '.').' dan sudah dibayar.')
            ->line('Pembeli: '.($this->bahanJadi->pembeli?->name ?? 'Pembeli').'.')
            ->action('Lihat Bahan Baku Jadi', url('/industri/bahan-jadi'))
            ->salutation('Salam hijau, Tim Daurin ♻️');
    }
}
