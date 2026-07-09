<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Potvrďte Vašu rezerváciu športoviska',
        );
    }

    public function content(): Content
    {
        $verificationUrl = rtrim(config('app.url'), '/') . '/rezervacia/potvrdenie/' . $this->reservation->id . '/' . $this->reservation->verification_token;

        return new Content(
            view: 'emails.reservation_verification',
            with: [
                'reservation' => $this->reservation,
                'url' => $verificationUrl,
            ],
        );
    }
}
