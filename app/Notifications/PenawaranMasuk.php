<?php

namespace App\Notifications;

use App\Models\PenawaranListing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Ke Rumah Tangga: ada pengepul menawar harga untuk listing sampahnya.
 */
class PenawaranMasuk extends Notification implements ShouldQueue
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
        $this->penawaran->loadMissing(['listing', 'pengepul']);
        $listing = $this->penawaran->listing;

        return (new MailMessage)
            ->subject('Penawaran Harga Masuk — Daurin')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line($this->penawaran->pengepul->name.' menawar Rp '.number_format((float) $this->penawaran->harga, 0, ',', '.').' untuk '.$listing->jenis_sampah->label().' ('.$listing->berat.' kg) Anda.')
            ->line('Anda bisa menerima atau menolak penawaran ini.')
            ->action('Lihat Penawaran', url('/rt'))
            ->salutation('Salam hijau, Tim Daurin ♻️');
    }
}
