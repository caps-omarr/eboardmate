<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OwnerDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $owner = $request->user();

        $boardingHouse = BoardingHouse::query()
            ->where('owner_id', $owner->id)
            ->with('primaryPhoto')
            ->first();

        if (! $boardingHouse) {
            return Inertia::render('Owner/Dashboard', [
                'owner' => [
                    'name' => $owner->name,
                    'email' => $owner->email,
                ],
                'boardingHouse' => null,
                'stats' => [
                    'total' => 0,
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'expired' => 0,
                ],
                'reservations' => [],
            ]);
        }

        $stats = [
            'total' => Reservation::where('boarding_house_id', $boardingHouse->id)->count(),
            'pending' => Reservation::where('boarding_house_id', $boardingHouse->id)
                ->where('status', Reservation::STATUS_PENDING)
                ->count(),
            'approved' => Reservation::where('boarding_house_id', $boardingHouse->id)
                ->where('status', Reservation::STATUS_APPROVED)
                ->count(),
            'rejected' => Reservation::where('boarding_house_id', $boardingHouse->id)
                ->where('status', Reservation::STATUS_REJECTED)
                ->count(),
            'expired' => Reservation::where('boarding_house_id', $boardingHouse->id)
                ->where('status', Reservation::STATUS_EXPIRED)
                ->count(),
        ];

        $reservations = Reservation::query()
            ->where('boarding_house_id', $boardingHouse->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (Reservation $reservation) {
                return [
                    'id' => $reservation->id,
                    'reference_code' => $reservation->reference_code,
                    'guest_name' => $reservation->guest_name,
                    'guest_email' => $reservation->guest_email,
                    'guest_phone' => $reservation->guest_phone,
                    'preferred_move_in_date' => $reservation->preferred_move_in_date?->format('M d, Y'),
                    'status' => $reservation->status,
                    'status_label' => $this->statusLabel($reservation->status),
                    'message' => $reservation->message,
                    'owner_response' => $reservation->owner_response,
                    'email_notification_status' => $reservation->email_notification_status,
                    'can_respond' => $reservation->status === Reservation::STATUS_PENDING,
                    'approve_url' => route('owner.reservations.approve', $reservation->id),
                    'reject_url' => route('owner.reservations.reject', $reservation->id),
                    'created_at' => $reservation->created_at?->format('M d, Y h:i A'),
                    'expires_at' => $reservation->expires_at?->format('M d, Y h:i A'),
                ];
            });

        return Inertia::render('Owner/Dashboard', [
            'owner' => [
                'name' => $owner->name,
                'email' => $owner->email,
            ],
            'boardingHouse' => [
                'id' => $boardingHouse->id,
                'name' => $boardingHouse->name,
                'slug' => $boardingHouse->slug,
                'status' => $boardingHouse->status,
                'is_verified' => $boardingHouse->is_verified,
                'rent_price' => (float) $boardingHouse->rent_price,
                'available_rooms' => $boardingHouse->available_rooms,
                'total_rooms' => $boardingHouse->total_rooms,
                'available_bedspaces' => $boardingHouse->available_bedspaces,
                'total_bedspaces' => $boardingHouse->total_bedspaces,
            ],
            'stats' => $stats,
            'reservations' => $reservations,
        ]);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            Reservation::STATUS_PENDING => 'Pending',
            Reservation::STATUS_APPROVED => 'Approved',
            Reservation::STATUS_REJECTED => 'Rejected / Declined',
            Reservation::STATUS_EXPIRED => 'Expired',
            Reservation::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($status),
        };
    }
}