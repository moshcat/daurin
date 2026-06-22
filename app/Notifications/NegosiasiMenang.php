<?php

namespace App\Notifications;

use App\Models\Lelang;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Ke Industri pemenang: negosiasi dimenangkan.
 */
class NegosiasiMenang extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lelang $lelang) {}

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
            ->subject('🏆 Anda Memenangkan Negosiasi — Daurin')
            ->greeting('Selamat, '.$notifiable->name.'!')
            ->line('Anda memenangkan '.$jenis.' seharga Rp '.number_format((float) $this->lelang->harga_final, 0, ',', '.').'.')
            ->line('Pesanan otomatis dibuat. Silakan lanjutkan ke proses transaksi & pengolahan.')
            ->action('Lihat Pesanan', url('/dashboard'))
            ->salutation('Salam hijau, Tim Daurin ♻️');
    }
}
