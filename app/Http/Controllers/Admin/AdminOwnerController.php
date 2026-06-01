<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminOwnerController extends Controller
{
    public function index(): Response
    {
        $owners = User::query()
            ->where('role', User::ROLE_OWNER)
            ->with('boardingHouse')
            ->latest()
            ->get()
            ->map(function (User $owner) {
                return [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'phone' => $owner->phone,
                    'status' => $owner->status,
                    'created_at' => $owner->created_at?->format('M d, Y h:i A'),
                    'boarding_house' => $owner->boardingHouse ? [
                        'id' => $owner->boardingHouse->id,
                        'name' => $owner->boardingHouse->name,
                        'status' => $owner->boardingHouse->status,
                    ] : null,
                    'toggle_status_url' => route('admin.owners.toggle-status', $owner->id),
                ];
            });

        return Inertia::render('Admin/Owners/Index', [
            'owners' => $owners,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $owner = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_OWNER,
            'status' => User::STATUS_ACTIVE,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => ActivityLog::ACTION_OWNER_CREATED,
            'description' => 'Super admin created owner account for ' . $owner->email . '.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Owner account created successfully.');
    }

    public function toggleStatus(Request $request, User $owner): RedirectResponse
    {
        abort_unless($owner->role === User::ROLE_OWNER, 404);

        $newStatus = $owner->status === User::STATUS_ACTIVE
            ? User::STATUS_INACTIVE
            : User::STATUS_ACTIVE;

        $owner->update([
            'status' => $newStatus,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'owner_status_updated',
            'description' => 'Super admin changed owner account status for ' . $owner->email . ' to ' . $newStatus . '.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Owner account status updated successfully.');
    }
}