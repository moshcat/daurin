<?php

namespace App\Notifications;

use App\Models\Lelang;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Ke Pengepul (penjual): negosiasi berakhir tanpa pemenang.
 */
class NegosiasiGagal extends Notification implements ShouldQueue
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
            ->subject('Negosiasi Berakhir Tanpa Pemenang — Daurin')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Negosiasi untuk '.$jenis.' Anda berakhir tanpa pemenang (tanpa tawaran atau di bawah harga minimum).')
            ->line('Barang dikembalikan ke status tersedia — Anda bisa membuka negosiasi lagi kapan saja.')
            ->action('Buka Negosiasi Lagi', url('/pengepul/bahan-baku'))
            ->salutation('Salam hijau, Tim Daurin ♻️');
    }
}
