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

        $totalOwners = User::where('role', User::ROLE_OWNER)->count();
        $activeOwners = User::where('role', User::ROLE_OWNER)
            ->where('status', User::STATUS_ACTIVE)
            ->count();
        $inactiveOwners = max(0, $totalOwners - $activeOwners);

        $totalBoardingHouses = BoardingHouse::count();
        $pendingListings = BoardingHouse::where('status', BoardingHouse::STATUS_PENDING)->count();
        $approvedListings = BoardingHouse::where('status', BoardingHouse::STATUS_APPROVED)->count();
        $rejectedListings = BoardingHouse::where('status', BoardingHouse::STATUS_REJECTED)->count();
        $deactivatedListings = BoardingHouse::where('status', BoardingHouse::STATUS_DEACTIVATED)->count();

        $totalRooms = (int) BoardingHouse::sum('total_rooms');
        $availableRooms = (int) BoardingHouse::sum('available_rooms');
        $totalBedspaces = (int) BoardingHouse::sum('total_bedspaces');
        $availableBedspaces = (int) BoardingHouse::sum('available_bedspaces');
        $occupiedBedspaces = max(0, $totalBedspaces - $availableBedspaces);

        $occupancyRate = $totalBedspaces > 0
            ? round(($occupiedBedspaces / $totalBedspaces) * 100, 1)
            : 0;

        $verificationRate = $totalBoardingHouses > 0
            ? round(($approvedListings / $totalBoardingHouses) * 100, 1)
            : 0;

        $stats = [
            'owners' => $totalOwners,
            'active_owners' => $activeOwners,
            'inactive_owners' => $inactiveOwners,
            'boarding_houses' => $totalBoardingHouses,
            'pending_listings' => $pendingListings,
            'approved_listings' => $approvedListings,
            'rejected_listings' => $rejectedListings,
            'deactivated_listings' => $deactivatedListings,
            'total_rooms' => $totalRooms,
            'available_rooms' => $availableRooms,
            'total_bedspaces' => $totalBedspaces,
            'available_bedspaces' => $availableBedspaces,
            'occupied_bedspaces' => $occupiedBedspaces,
            'occupancy_rate' => $occupancyRate,
            'verification_rate' => $verificationRate,
            'reservations' => Reservation::count(),
            'pending_reservations' => Reservation::where('status', Reservation::STATUS_PENDING)->count(),
            'approved_reservations' => Reservation::where('status', Reservation::STATUS_APPROVED)->count(),
        ];

        $latestBoardingHouses = BoardingHouse::query()
            ->with('owner:id,name,email')
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
                    'latitude' => $boardingHouse->latitude,
                    'longitude' => $boardingHouse->longitude,
                    'created_at' => $boardingHouse->created_at?->format('M d, Y h:i A'),
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'admin' => [
                'name' => $admin->name,
                'email' => $admin->email,
            ],
            'stats' => $stats,
            'latestBoardingHouses' => $latestBoardingHouses,
        ]);
    }
}