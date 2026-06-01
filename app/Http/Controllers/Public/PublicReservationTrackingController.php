<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicReservationTrackingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Public/TrackReservation');
    }

    public function search(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference_code' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $referenceCode = strtoupper(trim($validated['reference_code']));
        $email = strtolower(trim($validated['email']));

        $reservation = Reservation::query()
            ->with('boardingHouse')
            ->where('reference_code', $referenceCode)
            ->whereRaw('LOWER(guest_email) = ?', [$email])
            ->first();

        if (! $reservation) {
            throw ValidationException::withMessages([
                'tracking' => 'No reservation found. Please check your reference code and email address.',
            ]);
        }

        $this->expireReservationIfNeeded($reservation);

        $reservation->refresh();
        $reservation->load('boardingHouse');

        return redirect()
            ->route('track-reservation')
            ->with('tracking_result', [
                'reference_code' => $reservation->reference_code,
                'boarding_house_name' => $reservation->boardingHouse?->name ?? 'Boarding house not available',
                'status' => $reservation->status,
                'status_label' => $this->statusLabel($reservation->status),
                'status_type' => $this->statusType($reservation->status),
                'status_message' => $this->statusMessage($reservation),
                'submitted_at' => $reservation->created_at?->format('M d, Y h:i A'),
                'preferred_move_in_date' => $reservation->preferred_move_in_date?->format('M d, Y'),
                'expires_at' => $reservation->expires_at?->format('M d, Y h:i A'),
                'expired_at' => $reservation->expired_at?->format('M d, Y h:i A'),
                'approved_at' => $reservation->approved_at?->format('M d, Y h:i A'),
                'rejected_at' => $reservation->rejected_at?->format('M d, Y h:i A'),
                'cancelled_at' => $reservation->cancelled_at?->format('M d, Y h:i A'),
                'owner_response' => $reservation->owner_response,
                'is_expired' => $reservation->status === Reservation::STATUS_EXPIRED,
                'can_apply_again' => in_array($reservation->status, [
                    Reservation::STATUS_REJECTED,
                    Reservation::STATUS_EXPIRED,
                    Reservation::STATUS_CANCELLED,
                ], true),
            ]);
    }

    private function expireReservationIfNeeded(Reservation $reservation): void
    {
        if (! $reservation->hasExpiredByTime()) {
            return;
        }

        $reservation->update([
            'status' => Reservation::STATUS_EXPIRED,
            'expired_at' => now(),
        ]);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            Reservation::STATUS_PENDING => 'Pending',
            Reservation::STATUS_APPROVED => 'Approved',
            Reservation::STATUS_REJECTED => 'Rejected / Declined',
            Reservation::STATUS_EXPIRED => 'Reference Code Expired',
            Reservation::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($status),
        };
    }

    private function statusType(string $status): string
    {
        return match ($status) {
            Reservation::STATUS_PENDING => 'warning',
            Reservation::STATUS_APPROVED => 'success',
            Reservation::STATUS_REJECTED => 'danger',
            Reservation::STATUS_EXPIRED => 'secondary',
            Reservation::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    private function statusMessage(Reservation $reservation): string
    {
        return match ($reservation->status) {
            Reservation::STATUS_PENDING => 'Your reservation is still pending. Please wait for the boarding house owner to approve or decline your request.',
            Reservation::STATUS_APPROVED => 'Your reservation has been approved. Please check the owner response or your email notification if available.',
            Reservation::STATUS_REJECTED => 'Your reservation was declined by the boarding house owner. You may submit another reservation if needed.',
            Reservation::STATUS_EXPIRED => 'Reference code expired. This pending reservation was not approved or declined within 24 hours, so it is no longer active.',
            Reservation::STATUS_CANCELLED => 'This reservation has been cancelled and is no longer active.',
            default => 'Reservation status is available.',
        };
    }
}