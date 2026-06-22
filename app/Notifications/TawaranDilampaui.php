<?php

namespace App\Notifications;

use App\Models\Lelang;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Ke Industri: tawarannya sudah dilampaui penawar lain.
 */
class TawaranDilampaui extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lelang $lelang,
        public float $hargaTertinggi,
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
            ->subject('Tawaran Anda Dilampaui — Daurin')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Tawaran Anda untuk '.$jenis.' telah dilampaui penawar lain.')
            ->line('Tawaran tertinggi sekarang: Rp '.number_format($this->hargaTertinggi, 0, ',', '.').'.')
            ->line('Masih ada waktu untuk menawar lagi sebelum negosiasi ditutup.')
            ->action('Tawar Lagi', url('/lelang/'.$this->lelang->id))
            ->salutation('Salam hijau, Tim Daurin ♻️');
    }
}
