<?php

namespace App\Notifications;

use App\Models\PenawaranListing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Ke Pengepul: penawaran harganya diterima rumah tangga.
 */
class PenawaranDiterima extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PenawaranListing $penawaran) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->penawaran->loadMissing('listing');
        $listing = $this->penawaran->listing;

        return (new MailMessage)
            ->subject('Penawaran Anda Diterima — Daurin')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Penawaran Anda sebesar Rp '.number_format((float) $this->penawaran->harga, 0, ',', '.').' untuk '.$listing->jenis_sampah->label().' ('.$listing->berat.' kg) diterima.')
            ->line('Listing kini menjadi milik Anda — silakan jemput dan pilah menjadi bahan baku.')
            ->action('Lihat Bahan Baku', url('/pengepul/bahan-baku'))
            ->salutation('Salam hijau, Tim Daurin ♻️');
    }
}
