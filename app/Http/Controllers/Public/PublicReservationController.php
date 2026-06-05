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
use Illuminate\Database\QueryException; // 🚀 Required for the retry loop

class PublicReservationController extends Controller
{
    public function store(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        abort_unless($boardingHouse->isPubliclyVisible(), 404);

        // 1. Strict Domain Validation
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

        $attempts = 0;
        $maxAttempts = 3;
        $reservation = null;

        // 2. 🛡️ The Concurrency-Safe Retry Loop & Transaction
        while ($attempts < $maxAttempts) {
            try {
                $reservation = DB::transaction(function () use ($boardingHouse, $validated, $request, $normalizedFullName, $normalizedEmail, $normalizedPhone) {
                    
                    // LOCK THE ROW
                    $lockedHouse = BoardingHouse::where('id', $boardingHouse->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    // Expire old ones strictly for this house
                    $this->expireOldPendingReservations($lockedHouse);

                    // Re-check capacity while the row is locked
                    if ($lockedHouse->isFull()) {
                        throw ValidationException::withMessages([
                            'reservation' => 'This boarding house is currently full. Reservation is unavailable.',
                        ]);
                    }

                    // GLOBAL ANTI-HOARDING CHECK
                    $activeDuplicateExists = Reservation::query()
                        ->whereIn('status', [
                            Reservation::STATUS_PENDING,
                            Reservation::STATUS_APPROVED,
                        ])
                        ->where(function ($query) use ($normalizedEmail, $normalizedPhone) {
                            $query->whereRaw('LOWER(guest_email) = ?', [$normalizedEmail])
                                  ->orWhere('guest_phone', $normalizedPhone);
                        })
                        ->exists();

                    if ($activeDuplicateExists) {
                        throw ValidationException::withMessages([
                            'reservation' => 'You already have an active reservation request in the system. You can only hold one reservation at a time to ensure fairness. Please wait for it to be processed or cancel it.',
                        ]);
                    }

                    // Create and return the reservation safely
                    return Reservation::create([
                        'boarding_house_id' => $lockedHouse->id,
                        'reference_code' => $this->generateReferenceCode(), // 🚀 Atomic Generation
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

                break; // 🚀 If transaction succeeds, break out of the retry loop

            } catch (QueryException $e) {
                // 🚀 If it's a Unique Constraint Violation (Code 23000), retry!
                if ($e->getCode() == 23000 && $attempts < $maxAttempts - 1) {
                    $attempts++;
                    usleep(100000); // Wait 100 milliseconds before retrying
                    continue;
                }
                throw $e; // Rethrow if it's a different database error
            }
        }

        // 3. Automate the Email Dispatch (Using QUEUE)
        try {
            Mail::to($reservation->guest_email)->queue(new ReservationSubmittedMail($reservation, $boardingHouse));
        } catch (\Exception $e) {
            Log::error('Failed to queue submission email to ' . $reservation->guest_email . '. Error: ' . $e->getMessage());
        }

        // 4. Return the success redirect
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

        // 🚀 CRITICAL FIX: withTrashed() allows it to see soft-deleted records!
        $latestReservation = Reservation::query()
            ->withTrashed() 
            ->where('reference_code', 'like', 'EBM-' . $year . '-%')
            ->lockForUpdate() // Locks the row so no one else can grab this number
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