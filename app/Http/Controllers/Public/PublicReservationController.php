<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Reservation;
use App\Mail\ReservationSubmittedMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PublicReservationController extends Controller
{
    public function store(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        abort_unless($boardingHouse->isPubliclyVisible(), 404);

        $this->expireOldPendingReservations($boardingHouse);

        if ($boardingHouse->isFull()) {
            throw ValidationException::withMessages([
                'reservation' => 'This boarding house is currently full. Reservation is unavailable.',
            ]);
        }

        // Strict Domain Validation
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 
                'string', 
                'email', 
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|outlook\.com)$/i'
            ],
            'phone' => ['required', 'string', 'max:30'],
            'preferred_move_in_date' => ['required', 'date', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:1000'],
            'accepted_terms' => ['accepted'],
        ], [
            'email.regex' => 'Please use a valid email address from standard providers (Gmail, Yahoo, or Outlook).',
        ]);

        $normalizedFullName = trim($validated['full_name']);
        $normalizedEmail = strtolower(trim($validated['email']));
        $normalizedPhone = trim($validated['phone']);

        $activeDuplicateExists = Reservation::query()
            ->where('boarding_house_id', $boardingHouse->id)
            ->whereIn('status', [
                Reservation::STATUS_PENDING,
                Reservation::STATUS_APPROVED,
            ])
            ->where(function ($query) use ($normalizedFullName, $normalizedEmail, $normalizedPhone) {
                $query->where('guest_name', $normalizedFullName)
                    ->orWhereRaw('LOWER(guest_email) = ?', [$normalizedEmail])
                    ->orWhere('guest_phone', $normalizedPhone);
            })
            ->exists();

        if ($activeDuplicateExists) {
            throw ValidationException::withMessages([
                'reservation' => 'You already have an active reservation for this boarding house. Please track your existing reservation instead.',
            ]);
        }

        // 1. Save the reservation to the database
        $reservation = DB::transaction(function () use ($boardingHouse, $validated, $request, $normalizedFullName, $normalizedEmail, $normalizedPhone) {
            return Reservation::create([
                'boarding_house_id' => $boardingHouse->id,
                'reference_code' => $this->generateReferenceCode(),
                'guest_name' => $normalizedFullName,
                'guest_email' => $normalizedEmail,
                'guest_phone' => $normalizedPhone,
                'preferred_move_in_date' => $validated['preferred_move_in_date'],
                'message' => $validated['message'] ?? null,
                'status' => Reservation::STATUS_PENDING,
                'expires_at' => now()->addHours(24),
                'submission_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        // 2. Automate the Email Dispatch
        try {
            Mail::to($reservation->guest_email)->send(new ReservationSubmittedMail($reservation, $boardingHouse));
        } catch (\Exception $e) {
            // If the email fails (e.g. no internet, bad SMTP config), log it so the admin knows, 
            // but do not crash the user's screen. The database record is already safe.
            Log::error('Failed to send submission email to ' . $reservation->guest_email . '. Error: ' . $e->getMessage());
        }

        // 3. Return the success redirect
        return redirect()
            ->route('boarding-houses.show', $boardingHouse->slug)
            ->with('reservation_result', [
                'type' => 'success',
                'title' => 'Reservation Submitted Successfully',
                'message' => 'Your reservation request has been submitted. We also sent a copy of your reference code to your email.',
                'reference_code' => $reservation->reference_code,
                'boarding_house_name' => $boardingHouse->name,
                'tracking_email' => $reservation->guest_email,
                'status' => 'Pending',
                'expires_at' => $reservation->expires_at?->format('M d, Y h:i A'),
                'track_url' => url('/track-reservation'),
            ]);
    }

    private function expireOldPendingReservations(BoardingHouse $boardingHouse): void
    {
        Reservation::query()
            ->where('boarding_house_id', $boardingHouse->id)
            ->where('status', Reservation::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => Reservation::STATUS_EXPIRED,
                'expired_at' => now(),
            ]);
    }

    private function generateReferenceCode(): string
    {
        $year = now()->format('Y');

        $latestReservation = Reservation::query()
            ->where('reference_code', 'like', 'EBM-' . $year . '-%')
            ->latest('id')
            ->first();

        $nextNumber = 1;

        if ($latestReservation) {
            $latestNumber = (int) substr($latestReservation->reference_code, -6);
            $nextNumber = $latestNumber + 1;
        }

        return 'EBM-' . $year . '-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}