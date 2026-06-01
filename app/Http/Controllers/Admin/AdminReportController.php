<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminReportController extends Controller
{
    public function index(Request $request): Response
    {
        $stats = [
            'total_owners' => User::where('role', User::ROLE_OWNER)->count(),
            'total_boarding_houses' => BoardingHouse::count(),
            'approved_boarding_houses' => BoardingHouse::where('status', BoardingHouse::STATUS_APPROVED)->count(),
            'pending_boarding_houses' => BoardingHouse::where('status', BoardingHouse::STATUS_PENDING)->count(),
            'total_reservations' => Reservation::count(),
            'pending_reservations' => Reservation::where('status', Reservation::STATUS_PENDING)->count(),
            'approved_reservations' => Reservation::where('status', Reservation::STATUS_APPROVED)->count(),
            'rejected_reservations' => Reservation::where('status', Reservation::STATUS_REJECTED)->count(),
            'expired_reservations' => Reservation::where('status', Reservation::STATUS_EXPIRED)->count(),
            'cancelled_reservations' => Reservation::where('status', Reservation::STATUS_CANCELLED)->count(),
        ];

        $reservations = Reservation::query()
            ->with('boardingHouse')
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (Reservation $reservation) {
                return [
                    'id' => $reservation->id,
                    'reference_code' => $reservation->reference_code,
                    'guest_name' => $reservation->guest_name,
                    'guest_email' => $reservation->guest_email,
                    'guest_phone' => $reservation->guest_phone,
                    'boarding_house_name' => $reservation->boardingHouse?->name ?? 'Boarding house not available',
                    'preferred_move_in_date' => $reservation->preferred_move_in_date?->format('M d, Y'),
                    'status' => $reservation->status,
                    'status_label' => $this->statusLabel($reservation->status),
                    'owner_response' => $reservation->owner_response,
                    'created_at' => $reservation->created_at?->format('M d, Y h:i A'),
                    'responded_at' => $reservation->responded_at?->format('M d, Y h:i A'),
                ];
            });

        return Inertia::render('Admin/Reports/Index', [
            'stats' => $stats,
            'reservations' => $reservations,
            'generatedAt' => now()->format('M d, Y h:i A'),
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