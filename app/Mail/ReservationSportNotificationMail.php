<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal notification sent to the configured sport-department address
 * whenever a reservation is paid and auto-approved via card payment.
 */
class ReservationSportNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nova zaplatena rezervacia (karta)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservation_sport_notification',
            with: [
                'reservation' => $this->reservation,
            ],
        );
    }
}
