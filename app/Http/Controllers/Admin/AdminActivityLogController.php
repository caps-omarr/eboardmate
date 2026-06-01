<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class AdminActivityLogController extends Controller
{
    public function index(): Response
    {
        $activityLogs = ActivityLog::query()
            ->with(['user', 'boardingHouse', 'reservation'])
            ->latest()
            ->limit(150)
            ->get()
            ->map(function (ActivityLog $activityLog) {
                return [
                    'id' => $activityLog->id,
                    'action' => $activityLog->action,
                    'description' => $activityLog->description,
                    'user_name' => $activityLog->user?->name ?? 'System / Unknown User',
                    'user_email' => $activityLog->user?->email,
                    'boarding_house_name' => $activityLog->boardingHouse?->name,
                    'reservation_reference' => $activityLog->reservation?->reference_code,
                    'ip_address' => $activityLog->ip_address,
                    'created_at' => $activityLog->created_at?->format('M d, Y h:i A'),
                ];
            });

        return Inertia::render('Admin/ActivityLogs/Index', [
            'activityLogs' => $activityLogs,
        ]);
    }
}