<?php

namespace App\Mail;

use App\Models\Reservation;
use App\Services\ReservationPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationPaymentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Platobne udaje k Vasej rezervacii',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservation_payment',
            with: [
                'reservation' => $this->reservation,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn() => ReservationPdfService::instance()->generatePaymentSlip($this->reservation),
                "platba-{$this->reservation->variable_symbol}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
