<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationStatusMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Reservation $reservation
    ) {
        $this->reservation->loadMissing('boardingHouse');
    }

    public function envelope(): Envelope
    {
        $statusLabel = $this->statusLabel();

        return new Envelope(
            subject: 'E-BoardMate Reservation ' . $statusLabel . ' - ' . $this->reservation->reference_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reservations.status',
            with: [
                'reservation' => $this->reservation,
                'boardingHouse' => $this->reservation->boardingHouse,
                'statusLabel' => $this->statusLabel(),
                'statusMessage' => $this->statusMessage(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function statusLabel(): string
    {
        return match ($this->reservation->status) {
            Reservation::STATUS_APPROVED => 'Approved',
            Reservation::STATUS_REJECTED => 'Declined',
            default => ucfirst($this->reservation->status),
        };
    }

    private function statusMessage(): string
    {
        return match ($this->reservation->status) {
            Reservation::STATUS_APPROVED => 'Your boarding house reservation has been approved by the owner.',
            Reservation::STATUS_REJECTED => 'Your boarding house reservation has been declined by the owner.',
            default => 'Your boarding house reservation status has been updated.',
        };
    }
}