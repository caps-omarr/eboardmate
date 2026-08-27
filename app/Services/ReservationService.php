<?php

namespace App\Services;

use App\Mail\NewReservationNotification;
use App\Mail\ReservationSubmittedMail;
use App\Models\BoardingHouse;
use App\Models\Reservation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    /**
     * Create a concurrency-safe reservation for a boarding house
     *
     * @param BoardingHouse $boardingHouse
     * @param array $validatedData
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return Reservation
     * @throws ValidationException|\Throwable
     */
    public function createReservation(
        BoardingHouse $boardingHouse,
        array $validatedData,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Reservation {
        $normalizedFullName = trim($validatedData['full_name']);
        $normalizedEmail = strtolower(trim($validatedData['email']));
        $normalizedPhone = trim($validatedData['phone']);

        $attempts = 0;
        $maxAttempts = 3;
        $reservation = null;

        while ($attempts < $maxAttempts) {
            try {
                $reservation = DB::transaction(function () use (
                    $boardingHouse,
                    $validatedData,
                    $normalizedFullName,
                    $normalizedEmail,
                    $normalizedPhone,
                    $ipAddress,
                    $userAgent
                ) {
                    // Lock BoardingHouse row for atomic capacity check
                    $lockedHouse = BoardingHouse::where('id', $boardingHouse->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    // Automatically expire old pending reservations
                    $this->expireOldPendingReservations($lockedHouse);

                    // Verify capacity under lock
                    if ($lockedHouse->isFull()) {
                        throw ValidationException::withMessages([
                            'reservation' => 'This boarding house is currently full. Reservation is unavailable.',
                        ]);
                    }

                    // Anti-hoarding check: Only 1 active unexpired pending/approved reservation per email/phone
                    $activeDuplicateExists = Reservation::query()
                        ->whereIn('status', [
                            Reservation::STATUS_PENDING,
                            Reservation::STATUS_APPROVED,
                        ])
                        ->where(function ($query) use ($normalizedEmail, $normalizedPhone) {
                            $query->whereRaw('LOWER(guest_email) = ?', [$normalizedEmail])
                                ->orWhere('guest_phone', $normalizedPhone);
                        })
                        ->where(function ($query) {
                            $query->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        })
                        ->exists();

                    if ($activeDuplicateExists) {
                        throw ValidationException::withMessages([
                            'reservation' => 'You already have an active reservation request in the system. You can only hold one reservation at a time to ensure fairness.',
                        ]);
                    }

                    return Reservation::create([
                        'boarding_house_id' => $lockedHouse->id,
                        'reference_code' => $this->generateReferenceCode(),
                        'guest_name' => $normalizedFullName,
                        'guest_email' => $normalizedEmail,
                        'guest_phone' => $normalizedPhone,
                        'preferred_move_in_date' => $validatedData['preferred_move_in_date'],
                        'message' => $validatedData['message'] ?? null,
                        'status' => Reservation::STATUS_PENDING,
                        'expires_at' => now()->addHours(24),
                        'submission_ip' => $ipAddress,
                        'user_agent' => $userAgent,
                    ]);
                });

                break;

            } catch (QueryException $e) {
                if ($e->getCode() == 23000 && $attempts < $maxAttempts - 1) {
                    $attempts++;
                    usleep(100000); // 100ms backoff
                    continue;
                }
                throw $e;
            }
        }

        // Dispatch queued email notifications safely
        $this->dispatchNotifications($reservation, $boardingHouse);

        return $reservation;
    }

    /**
     * Expire stale pending reservations globally across all boarding houses
     */
    public function expireOldPendingReservations(?BoardingHouse $boardingHouse = null): void
    {
        Reservation::query()
            ->where('status', Reservation::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => Reservation::STATUS_EXPIRED,
                'expired_at' => now(),
            ]);
    }

    /**
     * Generate atomic reference code: EBM-YYYY-XXXXXX
     */
    private function generateReferenceCode(): string
    {
        $year = now()->format('Y');

        $latestReservation = Reservation::query()
            ->withTrashed()
            ->where('reference_code', 'like', 'EBM-' . $year . '-%')
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $nextNumber = 1;

        if ($latestReservation) {
            $latestNumber = (int) substr($latestReservation->reference_code, -6);
            $nextNumber = $latestNumber + 1;
        }

        return 'EBM-' . $year . '-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Dispatch queued emails to Guest and Owner safely
     */
    private function dispatchNotifications(Reservation $reservation, BoardingHouse $boardingHouse): void
    {
        try {
            Mail::to($reservation->guest_email)->queue(new ReservationSubmittedMail($reservation, $boardingHouse));
        } catch (\Exception $e) {
            Log::error('Failed to queue submission email to guest ' . $reservation->guest_email . ': ' . $e->getMessage());
        }

        if ($boardingHouse->owner && $boardingHouse->owner->email) {
            try {
                Mail::to($boardingHouse->owner->email)->queue(new NewReservationNotification($reservation, $boardingHouse));
            } catch (\Exception $e) {
                Log::error('Failed to queue new reservation notification to owner ' . $boardingHouse->owner->email . ': ' . $e->getMessage());
            }
        }
    }
}
