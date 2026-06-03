<?php

namespace App\Mail;

use App\Models\BoardingHouse;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public $boardingHouse;

    public function __construct(Reservation $reservation, BoardingHouse $boardingHouse)
    {
        $this->reservation = $reservation;
        $this->boardingHouse = $boardingHouse;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'E-BoardMate: Reservation Received [' . $this->reservation->reference_code . ']',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservation_submitted',
        );
    }
}