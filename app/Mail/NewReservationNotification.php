<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewReservationNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $reservation;
    public $boardingHouse;

    /**
     * Create a new message instance.
     */
    public function __construct($reservation, $boardingHouse)
    {
        $this->reservation = $reservation;
        $this->boardingHouse = $boardingHouse;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Reservation Request: ' . $this->boardingHouse->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.owner.new_reservation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}