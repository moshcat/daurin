<?php

namespace App\Notifications;

use App\Models\Lelang;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Ke Pengepul (penjual): barangnya terjual lewat negosiasi.
 */
class NegosiasiTerjualPenjual extends Notification implements ShouldQueue
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
        $this->lelang->loadMissing(['bahanBaku', 'pemenang']);
        $jenis = $this->lelang->bahanBaku->jenis_sampah->label();

        return (new MailMessage)
            ->subject('Barang Anda Terjual — Daurin')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line($jenis.' Anda terjual lewat negosiasi seharga Rp '.number_format((float) $this->lelang->harga_final, 0, ',', '.').'.')
            ->line('Pembeli: '.($this->lelang->pemenang?->name ?? 'Industri').'.')
            ->action('Lihat Detail', url('/lelang/'.$this->lelang->id))
            ->salutation('Salam hijau, Tim Daurin ♻️');
    }
}
