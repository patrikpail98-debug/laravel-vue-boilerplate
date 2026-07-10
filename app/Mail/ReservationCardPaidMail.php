<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationCardPaidMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Platba prijata, rezervacia je potvrdena',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservation_card_paid',
            with: [
                'reservation' => $this->reservation,
            ],
        );
    }
}
