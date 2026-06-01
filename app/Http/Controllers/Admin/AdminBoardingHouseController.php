<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BoardingHouse;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminBoardingHouseController extends Controller
{
    public function index(): Response
    {
        $owners = User::query()
            ->where('role', User::ROLE_OWNER)
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get()
            ->map(function (User $owner) {
                return [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                ];
            });

        $boardingHouses = BoardingHouse::query()
            ->with('owner')
            ->latest()
            ->get()
            ->map(function (BoardingHouse $boardingHouse) {
                return [
                    'id' => $boardingHouse->id,
                    'name' => $boardingHouse->name,
                    'slug' => $boardingHouse->slug,
                    'owner_id' => $boardingHouse->owner_id,
                    'owner_name' => $boardingHouse->owner?->name ?? 'No owner assigned',
                    'owner_email' => $boardingHouse->owner?->email,
                    'description' => $boardingHouse->description,
                    'location_description' => $boardingHouse->location_description,
                    'address' => $boardingHouse->address,
                    'latitude' => $boardingHouse->latitude,
                    'longitude' => $boardingHouse->longitude,
                    'rent_price' => (float) $boardingHouse->rent_price,
                    'available_rooms' => $boardingHouse->available_rooms,
                    'total_rooms' => $boardingHouse->total_rooms,
                    'available_bedspaces' => $boardingHouse->available_bedspaces,
                    'total_bedspaces' => $boardingHouse->total_bedspaces,
                    'status' => $boardingHouse->status,
                    'is_verified' => $boardingHouse->is_verified,
                    'rejection_reason' => $boardingHouse->rejection_reason,
                    'deactivated_reason' => $boardingHouse->deactivated_reason,
                    'created_at' => $boardingHouse->created_at?->format('M d, Y h:i A'),
                    'update_url' => route('admin.boarding-houses.update', $boardingHouse->id),
                    'approve_url' => route('admin.boarding-houses.approve', $boardingHouse->id),
                    'reject_url' => route('admin.boarding-houses.reject', $boardingHouse->id),
                    'deactivate_url' => route('admin.boarding-houses.deactivate', $boardingHouse->id),
                    'reactivate_url' => route('admin.boarding-houses.reactivate', $boardingHouse->id),
                ];
            });

        return Inertia::render('Admin/BoardingHouses/Index', [
            'owners' => $owners,
            'boardingHouses' => $boardingHouses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateBoardingHouseData($request);

        $boardingHouse = BoardingHouse::create([
            'owner_id' => $validated['owner_id'] ?? null,
            'name' => trim($validated['name']),
            'slug' => $this->generateUniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'location_description' => $validated['location_description'] ?? null,
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'rent_price' => $validated['rent_price'],
            'total_rooms' => $validated['total_rooms'],
            'available_rooms' => $validated['available_rooms'],
            'total_bedspaces' => $validated['total_bedspaces'],
            'available_bedspaces' => $validated['available_bedspaces'],
            'status' => BoardingHouse::STATUS_PENDING,
            'is_verified' => false,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'boarding_house_id' => $boardingHouse->id,
            'action' => ActivityLog::ACTION_BOARDING_HOUSE_CREATED,
            'description' => 'Super admin created boarding house listing ' . $boardingHouse->name . '.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Boarding house listing created successfully. It is currently pending verification.');
    }

    public function update(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        $validated = $this->validateBoardingHouseData($request);

        $boardingHouse->update([
            'owner_id' => $validated['owner_id'] ?? null,
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'location_description' => $validated['location_description'] ?? null,
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'rent_price' => $validated['rent_price'],
            'total_rooms' => $validated['total_rooms'],
            'available_rooms' => $validated['available_rooms'],
            'total_bedspaces' => $validated['total_bedspaces'],
            'available_bedspaces' => $validated['available_bedspaces'],
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'boarding_house_id' => $boardingHouse->id,
            'action' => ActivityLog::ACTION_COORDINATES_UPDATED,
            'description' => 'Super admin updated boarding house listing ' . $boardingHouse->name . '.',
            'properties' => [
                'owner_id' => $boardingHouse->owner_id,
                'latitude' => $boardingHouse->latitude,
                'longitude' => $boardingHouse->longitude,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Boarding house listing updated successfully.');
    }

    public function approve(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        if (! $boardingHouse->latitude || ! $boardingHouse->longitude) {
            throw ValidationException::withMessages([
                'listing' => 'Cannot approve this listing because latitude and longitude are missing.',
            ]);
        }

        $boardingHouse->update([
            'status' => BoardingHouse::STATUS_APPROVED,
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
            'rejection_reason' => null,
            'deactivated_reason' => null,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'boarding_house_id' => $boardingHouse->id,
            'action' => ActivityLog::ACTION_LISTING_VERIFIED,
            'description' => 'Super admin approved and verified listing ' . $boardingHouse->name . '.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Boarding house listing approved and verified successfully.');
    }

    public function reject(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $boardingHouse->update([
            'status' => BoardingHouse::STATUS_REJECTED,
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
            'rejection_reason' => $validated['reason'],
            'deactivated_reason' => null,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'boarding_house_id' => $boardingHouse->id,
            'action' => ActivityLog::ACTION_LISTING_REJECTED,
            'description' => 'Super admin rejected listing ' . $boardingHouse->name . '.',
            'properties' => [
                'reason' => $validated['reason'],
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Boarding house listing rejected successfully.');
    }

    public function deactivate(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $boardingHouse->update([
            'status' => BoardingHouse::STATUS_DEACTIVATED,
            'is_verified' => false,
            'deactivated_reason' => $validated['reason'],
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'boarding_house_id' => $boardingHouse->id,
            'action' => ActivityLog::ACTION_LISTING_DEACTIVATED,
            'description' => 'Super admin deactivated listing ' . $boardingHouse->name . '.',
            'properties' => [
                'reason' => $validated['reason'],
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Boarding house listing deactivated successfully.');
    }

    public function reactivate(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        if (! $boardingHouse->latitude || ! $boardingHouse->longitude) {
            throw ValidationException::withMessages([
                'listing' => 'Cannot reactivate this listing because latitude and longitude are missing.',
            ]);
        }

        $boardingHouse->update([
            'status' => BoardingHouse::STATUS_APPROVED,
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
            'rejection_reason' => null,
            'deactivated_reason' => null,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'boarding_house_id' => $boardingHouse->id,
            'action' => ActivityLog::ACTION_LISTING_REACTIVATED,
            'description' => 'Super admin reactivated listing ' . $boardingHouse->name . '.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Boarding house listing reactivated successfully.');
    }

    private function validateBoardingHouseData(Request $request): array
    {
        $validated = $request->validate([
            'owner_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_OWNER),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'location_description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'rent_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'total_rooms' => ['required', 'integer', 'min:0', 'max:999'],
            'available_rooms' => ['required', 'integer', 'min:0', 'max:999'],
            'total_bedspaces' => ['required', 'integer', 'min:0', 'max:9999'],
            'available_bedspaces' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        if ($validated['available_rooms'] > $validated['total_rooms']) {
            throw ValidationException::withMessages([
                'available_rooms' => 'Available rooms cannot be greater than total rooms.',
            ]);
        }

        if ($validated['available_bedspaces'] > $validated['total_bedspaces']) {
            throw ValidationException::withMessages([
                'available_bedspaces' => 'Available bedspaces cannot be greater than total bedspaces.',
            ]);
        }

        return $validated;
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (BoardingHouse::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}