<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreReservationRequest;
use App\Models\BoardingHouse;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;

class PublicReservationController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService
    ) {}

    public function store(StoreReservationRequest $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        abort_unless($boardingHouse->isPubliclyVisible(), 404);

        $reservation = $this->reservationService->createReservation(
            $boardingHouse,
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

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
}