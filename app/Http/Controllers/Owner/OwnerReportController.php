<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class OwnerReportController extends Controller
{
    /**
     * Export a printable landscape PDF rent ledger for approved reservations.
     */
    public function exportReservationsPdf(Request $request)
    {
        $owner = $request->user();

        $boardingHouse = BoardingHouse::query()
            ->where('owner_id', $owner->id)
            ->first();

        if (! $boardingHouse) {
            abort(404, 'No boarding house assigned to your owner account.');
        }

        // Parse requested month_year (format: YYYY-MM) or default to current month
        $monthYearParam = $request->query('month_year', now()->format('Y-m'));

        try {
            $selectedCarbon = Carbon::createFromFormat('Y-m', $monthYearParam)->startOfMonth();
        } catch (\Throwable $e) {
            $selectedCarbon = now()->startOfMonth();
            $monthYearParam = $selectedCarbon->format('Y-m');
        }

        $selectedMonthFormatted = $selectedCarbon->format('F Y');
        $month1Label = $selectedCarbon->format('M Y');
        $month2Label = $selectedCarbon->copy()->addMonth()->format('M Y');
        $month3Label = $selectedCarbon->copy()->addMonths(2)->format('M Y');

        // Query approved reservations for this property
        // Eagerly sort by move-in date or guest name for a clean ledger
        $reservations = Reservation::query()
            ->where('boarding_house_id', $boardingHouse->id)
            ->where('status', Reservation::STATUS_APPROVED)
            ->orderByRaw('preferred_move_in_date IS NULL, preferred_move_in_date ASC')
            ->orderBy('guest_name')
            ->get();

        $data = [
            'boardingHouse' => $boardingHouse,
            'owner' => $owner,
            'reservations' => $reservations,
            'selectedMonth' => $monthYearParam,
            'selectedMonthFormatted' => $selectedMonthFormatted,
            'month1Label' => $month1Label,
            'month2Label' => $month2Label,
            'month3Label' => $month3Label,
            'generatedAt' => now()->format('M d, Y h:i A'),
        ];

        $pdf = Pdf::loadView('reports.owner-reservations-ledger', $data)
            ->setPaper('letter', 'landscape')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        $safeName = Str::slug($boardingHouse->name ?: 'property');
        $filename = "rent-ledger-{$safeName}-{$monthYearParam}.pdf";

        return $pdf->stream($filename);
    }
}
