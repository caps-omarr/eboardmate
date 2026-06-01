<?php

namespace App\Services;

use App\Mail\ReservationStatusMail;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ReservationNotificationService
{
    public function sendStatusEmail(Reservation $reservation): void
    {
        try {
            $reservation->loadMissing('boardingHouse');

            Mail::to($reservation->guest_email)
                ->send(new ReservationStatusMail($reservation));

            $reservation->update([
                'email_notification_sent_at' => now(),
                'email_notification_status' => Reservation::EMAIL_STATUS_SENT,
                'email_notification_error' => null,
            ]);
        } catch (Throwable $exception) {
            $safeErrorMessage = $this->safeErrorMessage($exception);

            $reservation->update([
                'email_notification_status' => Reservation::EMAIL_STATUS_FAILED,
                'email_notification_error' => $safeErrorMessage,
            ]);

            Log::warning('Reservation status email failed to send.', [
                'reservation_id' => $reservation->id,
                'reference_code' => $reservation->reference_code,
                'guest_email' => $reservation->guest_email,
                'error' => $safeErrorMessage,
            ]);
        }
    }

    private function safeErrorMessage(Throwable $exception): string
    {
        return str($exception->getMessage())
            ->limit(500)
            ->toString();
    }
}