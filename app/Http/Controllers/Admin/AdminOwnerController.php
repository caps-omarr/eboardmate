<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BoardingHouse;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminOwnerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->query('search', ''));

        $owners = User::query()
            ->select('id', 'name', 'email', 'phone', 'status', 'created_at')
            ->where('role', User::ROLE_OWNER)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->with(['boardingHouse' => function ($query) {
                $query->select('id', 'owner_id', 'name', 'status');
            }])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (User $owner) {
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
                    'update_url' => route('admin.owners.update', $owner->id),
                    'reset_password_url' => route('admin.owners.reset-password', $owner->id),
                    'delete_url' => route('admin.owners.destroy', $owner->id),
                ];
            });

        return Inertia::render('Admin/Owners/Index', [
            'owners' => $owners,
            'filters' => [
                'search' => $search,
            ],
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

        BoardingHouse::clearPublicCaches();

        return back()->with('success', 'Owner account created successfully.');
    }

    public function update(Request $request, User $owner): RedirectResponse
    {
        abort_unless($owner->role === User::ROLE_OWNER, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($owner->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $owner->update([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'owner_profile_updated',
            'description' => 'Super admin updated profile details for owner ' . $owner->email . '.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        BoardingHouse::clearPublicCaches();

        return back()->with('success', "Owner {$owner->name}'s profile was updated successfully.");
    }

    public function resetPassword(Request $request, User $owner): RedirectResponse
    {
        abort_unless($owner->role === User::ROLE_OWNER, 404);

        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        // Generate a clean, secure 10-character string if custom password was not provided
        $plainPassword = !empty($validated['password'])
            ? $validated['password']
            : Str::password(10, true, true, false, false);

        $owner->update([
            'password' => Hash::make($plainPassword),
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'owner_password_reset',
            'description' => 'Super admin reset password for owner ' . $owner->email . '.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Password for {$owner->name} ({$owner->email}) has been reset to: {$plainPassword}");
    }

    public function destroy(Request $request, User $owner): RedirectResponse
    {
        abort_unless($owner->role === User::ROLE_OWNER, 404);

        DB::transaction(function () use ($owner, $request) {
            // Safely unlink or deactivate any assigned boarding house
            if ($owner->boardingHouse) {
                $bh = $owner->boardingHouse;
                $bh->update([
                    'owner_id' => null,
                    'status' => BoardingHouse::STATUS_DEACTIVATED,
                    'is_verified' => false,
                    'deactivated_reason' => 'Assigned owner account was deleted by administrator.',
                ]);
                BoardingHouse::clearPublicCaches($bh->id);
            }

            $ownerEmail = $owner->email;
            $ownerName = $owner->name;
            $owner->delete();

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'owner_deleted',
                'description' => "Super admin deleted owner account {$ownerName} ({$ownerEmail}).",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            BoardingHouse::clearPublicCaches();
        });

        return back()->with('success', 'Owner account deleted successfully.');
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

        BoardingHouse::clearPublicCaches();
        if ($owner->boardingHouse) {
            Cache::forget("boarding_house_public_details_{$owner->boardingHouse->id}");
        }

        return back()->with('success', 'Owner account status updated successfully.');
    }
}