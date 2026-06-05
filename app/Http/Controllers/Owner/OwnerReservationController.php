<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BoardingHouse;
use App\Models\Reservation;
use App\Services\ReservationNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OwnerReservationController extends Controller
{
    public function index(Request $request): Response
    {
        
        $boardingHouse = BoardingHouse::query()
            ->select('id', 'name', 'status', 'is_verified')
            ->where('owner_id', $request->user()->id)
            ->first();

        if (! $boardingHouse) {
            return Inertia::render('Owner/Reservations/Index', [
                'boardingHouse' => null,
                'reservations' => [],
                'filters' => ['status' => 'all'],
            ]);
        }

        
        $statusFilter = $request->query('status', 'all');
        
        
        $query = Reservation::query()
            ->where('boarding_house_id', $boardingHouse->id);

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $reservations = $query->latest()->get()->map(function (Reservation $reservation) {
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
                'created_at' => $reservation->created_at?->format('M d, Y h:i A'),
                'expires_at' => $reservation->expires_at?->format('M d, Y h:i A'),
                'responded_at' => $reservation->responded_at?->format('M d, Y h:i A'),
                'can_respond' => $reservation->status === Reservation::STATUS_PENDING,
                'approve_url' => route('owner.reservations.approve', $reservation->id),
                'reject_url' => route('owner.reservations.reject', $reservation->id),
                'archive_url' => route('owner.reservations.archive', $reservation->id),
            ];
        });

        return Inertia::render('Owner/Reservations/Index', [
            'boardingHouse' => [
                'id' => $boardingHouse->id,
                'name' => $boardingHouse->name,
                'status' => $boardingHouse->status,
                'is_verified' => $boardingHouse->is_verified,
            ],
            'reservations' => $reservations,
            'filters' => ['status' => $statusFilter],
        ]);
    }

    public function archive(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->ensureOwnerCanManageReservation($request, $reservation);
        
        
        $reservation->delete();

        return back()->with('success', 'Reservation archived successfully.');
    }

    public function approve(
        Request $request,
        Reservation $reservation,
        ReservationNotificationService $notificationService
    ): RedirectResponse {
        $this->ensureOwnerCanManageReservation($request, $reservation);

        if (! $reservation->isPending()) {
            throw ValidationException::withMessages([
                'reservation' => 'Only pending reservations can be approved.',
            ]);
        }

        $validated = $request->validate([
            'owner_response' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $reservation, $validated) {
            $reservation->update([
                'status' => Reservation::STATUS_APPROVED,
                'owner_response' => $validated['owner_response'] ?? 'Your reservation has been approved. Please contact the boarding house owner for the next step.',
                'responded_at' => now(),
                'responded_by' => $request->user()->id,
                'approved_at' => now(),
                'rejected_at' => null,
            ]);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'boarding_house_id' => $reservation->boarding_house_id,
                'reservation_id' => $reservation->id,
                'action' => ActivityLog::ACTION_RESERVATION_APPROVED,
                'description' => 'Owner approved reservation ' . $reservation->reference_code . '.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

       
        $notificationService->sendStatusEmail($reservation->fresh());

        return back()->with('success', 'Reservation approved successfully.');
    }

    public function reject(
        Request $request,
        Reservation $reservation,
        ReservationNotificationService $notificationService
    ): RedirectResponse {
        $this->ensureOwnerCanManageReservation($request, $reservation);

        if (! $reservation->isPending()) {
            throw ValidationException::withMessages([
                'reservation' => 'Only pending reservations can be rejected.',
            ]);
        }

        $validated = $request->validate([
            'owner_response' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $reservation, $validated) {
            $reservation->update([
                'status' => Reservation::STATUS_REJECTED,
                'owner_response' => $validated['owner_response'],
                'responded_at' => now(),
                'responded_by' => $request->user()->id,
                'rejected_at' => now(),
                'approved_at' => null,
            ]);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'boarding_house_id' => $reservation->boarding_house_id,
                'reservation_id' => $reservation->id,
                'action' => ActivityLog::ACTION_RESERVATION_REJECTED,
                'description' => 'Owner rejected reservation ' . $reservation->reference_code . '.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        $notificationService->sendStatusEmail($reservation->fresh());

        return back()->with('success', 'Reservation rejected successfully.');
    }

    private function ensureOwnerCanManageReservation(Request $request, Reservation $reservation): void
    {
        
        $reservation->loadMissing('boardingHouse');

        if (! $reservation->boardingHouse || $reservation->boardingHouse->owner_id !== $request->user()->id) {
            abort(403, 'You are not allowed to manage this reservation.');
        }
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