<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationCardPaymentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation, public string $orderId)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Dokoncite platbu za rezervaciu sportoviska',
        );
    }

    public function content(): Content
    {
        $resumeUrl = rtrim(config('app.url'), '/') . '/rezervacia/platba/pokracovat/' . $this->orderId;

        return new Content(
            view: 'emails.reservation_card_payment',
            with: [
                'reservation' => $this->reservation,
                'url' => $resumeUrl,
            ],
        );
    }
}
