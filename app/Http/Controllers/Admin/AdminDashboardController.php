<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $admin = $request->user();

        $stats = [
            'owners' => User::where('role', User::ROLE_OWNER)->count(),
            'active_owners' => User::where('role', User::ROLE_OWNER)
                ->where('status', User::STATUS_ACTIVE)
                ->count(),
            'boarding_houses' => BoardingHouse::count(),
            'pending_listings' => BoardingHouse::where('status', BoardingHouse::STATUS_PENDING)->count(),
            'approved_listings' => BoardingHouse::where('status', BoardingHouse::STATUS_APPROVED)->count(),
            'rejected_listings' => BoardingHouse::where('status', BoardingHouse::STATUS_REJECTED)->count(),
            'deactivated_listings' => BoardingHouse::where('status', BoardingHouse::STATUS_DEACTIVATED)->count(),
            'reservations' => Reservation::count(),
            'pending_reservations' => Reservation::where('status', Reservation::STATUS_PENDING)->count(),
            'approved_reservations' => Reservation::where('status', Reservation::STATUS_APPROVED)->count(),
        ];

        $latestBoardingHouses = BoardingHouse::query()
            ->with('owner')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (BoardingHouse $boardingHouse) {
                return [
                    'id' => $boardingHouse->id,
                    'name' => $boardingHouse->name,
                    'slug' => $boardingHouse->slug,
                    'owner_name' => $boardingHouse->owner?->name ?? 'No assigned owner',
                    'owner_email' => $boardingHouse->owner?->email,
                    'status' => $boardingHouse->status,
                    'is_verified' => $boardingHouse->is_verified,
                    'rent_price' => (float) $boardingHouse->rent_price,
                    'available_rooms' => $boardingHouse->available_rooms,
                    'available_bedspaces' => $boardingHouse->available_bedspaces,
                    'created_at' => $boardingHouse->created_at?->format('M d, Y h:i A'),
                ];
            });

        $latestReservations = Reservation::query()
            ->with('boardingHouse')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (Reservation $reservation) {
                return [
                    'id' => $reservation->id,
                    'reference_code' => $reservation->reference_code,
                    'guest_name' => $reservation->guest_name,
                    'boarding_house_name' => $reservation->boardingHouse?->name ?? 'Boarding house not available',
                    'status' => $reservation->status,
                    'status_label' => $this->reservationStatusLabel($reservation->status),
                    'created_at' => $reservation->created_at?->format('M d, Y h:i A'),
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'admin' => [
                'name' => $admin->name,
                'email' => $admin->email,
            ],
            'stats' => $stats,
            'latestBoardingHouses' => $latestBoardingHouses,
            'latestReservations' => $latestReservations,
        ]);
    }

    private function reservationStatusLabel(string $status): string
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