<?php

namespace App\Notifications;

use App\Models\ListingSampah;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Ke Rumah Tangga: listing sampahnya diklaim pengepul.
 */
class ListingDiklaim extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ListingSampah $listing,
        public string $pengepulNama,
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
        return (new MailMessage)
            ->subject('Sampah Anda Diambil Pengepul — Daurin')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line($this->listing->jenis_sampah->label().' ('.$this->listing->berat.' kg) Anda telah diklaim oleh '.$this->pengepulNama.'.')
            ->line('Pengepul akan menjemput sesuai rute. Pantau statusnya di dashboard Anda.')
            ->action('Buka Dashboard', url('/dashboard'))
            ->salutation('Salam hijau, Tim Daurin ♻️');
    }
}
