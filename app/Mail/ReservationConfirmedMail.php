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
 * Booking confirmation. Implements ShouldQueue so it is pushed onto the queue
 * instead of being sent inside the request, keeping the response fast (NFR3).
 */
class ReservationConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Reservation $reservation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your BookMyHotel reservation '.$this->reservation->reference,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reservation-confirmed');
    }
}
