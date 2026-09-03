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
        $boardingHouseId = $request->query('boarding_house_id', 'all');
        $status = $request->query('status', 'all');
        $dateFrom = $request->query('date_from', '');
        $dateTo = $request->query('date_to', '');
        $search = trim($request->query('search', ''));

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

        // Boarding Houses selection list for filter dropdown
        $boardingHousesList = BoardingHouse::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Universal Reservations Query with eager loading to prevent N+1 queries
        $reservationsQuery = Reservation::query()
            ->with(['boardingHouse:id,name,address'])
            ->when($boardingHouseId !== 'all' && !empty($boardingHouseId), function ($q) use ($boardingHouseId) {
                $q->where('boarding_house_id', $boardingHouseId);
            })
            ->when($status !== 'all' && !empty($status), function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when(!empty($dateFrom), function ($q) use ($dateFrom) {
                $q->whereDate('preferred_move_in_date', '>=', $dateFrom);
            })
            ->when(!empty($dateTo), function ($q) use ($dateTo) {
                $q->whereDate('preferred_move_in_date', '<=', $dateTo);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('reference_code', 'like', "%{$search}%")
                        ->orWhere('guest_name', 'like', "%{$search}%")
                        ->orWhereHas('boardingHouse', function ($bhQ) use ($search) {
                            $bhQ->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest();

        $reservations = $reservationsQuery
            ->limit(250)
            ->get()
            ->map(function (Reservation $reservation) {
                $rawPhone = $reservation->guest_phone ?? '';
                // Data privacy: mask phone number (e.g. 0912***6789)
                $maskedPhone = (strlen($rawPhone) >= 7)
                    ? substr($rawPhone, 0, 4) . '***' . substr($rawPhone, -3)
                    : (empty($rawPhone) ? 'N/A' : '***');

                return [
                    'id' => $reservation->id,
                    'reference_code' => $reservation->reference_code,
                    'guest_name' => $reservation->guest_name,
                    'guest_email' => $reservation->guest_email,
                    'guest_phone_masked' => $maskedPhone,
                    'boarding_house_name' => $reservation->boardingHouse?->name ?? 'Boarding house not available',
                    'boarding_house_address' => $reservation->boardingHouse?->address ?? '',
                    'preferred_move_in_date' => $reservation->preferred_move_in_date?->format('M d, Y') ?? 'N/A',
                    'status' => $reservation->status,
                    'status_label' => $this->statusLabel($reservation->status),
                    'created_at' => $reservation->created_at?->format('M d, Y h:i A'),
                ];
            });

        // Boarding House Directory Report Dataset
        $directoryReport = BoardingHouse::query()
            ->with('owner:id,name,phone,email')
            ->orderBy('name')
            ->get()
            ->map(function (BoardingHouse $bh) {
                return [
                    'id' => $bh->id,
                    'name' => $bh->name,
                    'address' => $bh->address ?? 'Talibon, Bohol',
                    'latitude' => $bh->latitude,
                    'longitude' => $bh->longitude,
                    'owner_name' => $bh->owner?->name ?? 'No owner assigned',
                    'owner_phone' => $bh->owner?->phone ?? 'N/A',
                    'owner_email' => $bh->owner?->email ?? 'N/A',
                    'rent_price' => (float) $bh->rent_price,
                    'total_rooms' => $bh->total_rooms,
                    'available_rooms' => $bh->available_rooms,
                    'total_bedspaces' => $bh->total_bedspaces,
                    'available_bedspaces' => $bh->available_bedspaces,
                    'allowed_genders' => $bh->allowed_genders ?? 'Any Gender',
                    'status' => $bh->status,
                    'is_verified' => $bh->is_verified,
                ];
            });

        return Inertia::render('Admin/Reports/Index', [
            'stats' => $stats,
            'reservations' => $reservations,
            'directoryReport' => $directoryReport,
            'boardingHousesList' => $boardingHousesList,
            'filters' => [
                'boarding_house_id' => $boardingHouseId,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
            ],
            'generatedAt' => now()->format('F d, Y h:i A'),
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