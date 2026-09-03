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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminBoardingHouseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->query('search', ''));

        $owners = User::query()
            ->where('role', User::ROLE_OWNER)
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->select('id', 'name', 'email') 
            ->get();

        $boardingHouses = BoardingHouse::query()
            ->with('owner:id,name,email')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('owner', function ($ownerQuery) use ($search) {
                            $ownerQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(function (BoardingHouse $boardingHouse) {
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
                    'total_rooms' => $boardingHouse->total_rooms,
                    'available_rooms' => $boardingHouse->available_rooms,
                    'total_bedspaces' => $boardingHouse->total_bedspaces,
                    'available_bedspaces' => $boardingHouse->available_bedspaces,
                    'amenities' => $boardingHouse->amenities ?? [],
                    'rules' => $boardingHouse->rules,
                    'allowed_genders' => $boardingHouse->allowed_genders ?? 'Any Gender (All)',
                    'includes_water' => (bool) $boardingHouse->includes_water,
                    'includes_electricity' => (bool) $boardingHouse->includes_electricity,
                    'water_billing_details' => $boardingHouse->water_billing_details,
                    'electricity_billing_details' => $boardingHouse->electricity_billing_details,
                    'status' => $boardingHouse->status,
                    'is_verified' => $boardingHouse->is_verified,
                    'created_at' => $boardingHouse->created_at?->format('M d, Y h:i A'),
                    'update_url' => route('admin.boarding-houses.update', $boardingHouse->id),
                    'delete_url' => route('admin.boarding-houses.destroy', $boardingHouse->id),
                    'approve_url' => route('admin.boarding-houses.approve', $boardingHouse->id),
                    'reject_url' => route('admin.boarding-houses.reject', $boardingHouse->id),
                    'deactivate_url' => route('admin.boarding-houses.deactivate', $boardingHouse->id),
                    'reactivate_url' => route('admin.boarding-houses.reactivate', $boardingHouse->id),
                ];
            });

        return Inertia::render('Admin/BoardingHouses/Index', [
            'owners' => $owners,
            'boardingHouses' => $boardingHouses,
            'filters' => [
                'search' => $search,
            ],
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

        BoardingHouse::clearPublicCaches($boardingHouse->id);

        return back()->with('success', 'Boarding house listing created successfully.');
    }

    public function update(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        $validated = $this->validateBoardingHouseData($request);

        $includesWater = (bool) ($validated['includes_water'] ?? false);
        $includesElectricity = (bool) ($validated['includes_electricity'] ?? false);
        $waterBillingDetails = $includesWater ? null : ($validated['water_billing_details'] ?? null);
        $electricityBillingDetails = $includesElectricity ? null : ($validated['electricity_billing_details'] ?? null);

        $amenities = isset($validated['amenities_text'])
            ? collect(explode(',', $validated['amenities_text']))
                ->map(fn ($amenity) => trim($amenity))
                ->filter()
                ->unique()
                ->values()
                ->all()
            : ($boardingHouse->amenities ?? []);

        $updateData = [
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
            'amenities' => $amenities,
            'rules' => $validated['rules'] ?? null,
            'allowed_genders' => $validated['allowed_genders'] ?? 'Any Gender (All)',
            'includes_water' => $includesWater,
            'includes_electricity' => $includesElectricity,
            'water_billing_details' => $waterBillingDetails,
            'electricity_billing_details' => $electricityBillingDetails,
        ];

        $boardingHouse->update($updateData);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'boarding_house_id' => $boardingHouse->id,
            'action' => ActivityLog::ACTION_COORDINATES_UPDATED,
            'description' => 'Super admin updated boarding house listing ' . $boardingHouse->name . '.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        BoardingHouse::clearPublicCaches($boardingHouse->id);

        return back()->with('success', 'Boarding house listing updated successfully.');
    }

    public function destroy(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        DB::transaction(function () use ($boardingHouse, $request) {
            $houseName = $boardingHouse->name;
            $houseId = $boardingHouse->id;

            // Delete associated photos
            foreach ($boardingHouse->photos as $photo) {
                if ($photo->storage_path && \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->exists($photo->storage_path)) {
                    \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->delete($photo->storage_path);
                }
                $photo->delete();
            }

            // Cascade delete reservations
            $boardingHouse->reservations()->delete();

            // Delete boarding house
            $boardingHouse->delete();

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'boarding_house_deleted',
                'description' => "Super admin permanently deleted boarding house listing '{$houseName}'.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            BoardingHouse::clearPublicCaches($houseId);
        });

        return back()->with('success', 'Boarding house listing deleted successfully.');
    }

    public function approve(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        if (! $boardingHouse->latitude || ! $boardingHouse->longitude) {
            throw ValidationException::withMessages(['listing' => 'Missing coordinates.']);
        }

        $boardingHouse->update([
            'status' => BoardingHouse::STATUS_APPROVED,
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);

        BoardingHouse::clearPublicCaches($boardingHouse->id);

        return back()->with('success', 'Approved and verified successfully.');
    }

    public function reject(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:1000']])['reason'];

        $boardingHouse->update(['status' => BoardingHouse::STATUS_REJECTED, 'is_verified' => false, 'rejection_reason' => $reason]);

        BoardingHouse::clearPublicCaches($boardingHouse->id);

        return back()->with('success', 'Listing rejected successfully.');
    }

    public function deactivate(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:1000']])['reason'];

        $boardingHouse->update(['status' => BoardingHouse::STATUS_DEACTIVATED, 'is_verified' => false, 'deactivated_reason' => $reason]);

        BoardingHouse::clearPublicCaches($boardingHouse->id);

        return back()->with('success', 'Listing deactivated successfully.');
    }

    public function reactivate(Request $request, BoardingHouse $boardingHouse): RedirectResponse
    {
        if (! $boardingHouse->latitude || ! $boardingHouse->longitude) {
            throw ValidationException::withMessages(['listing' => 'Missing coordinates.']);
        }

        $boardingHouse->update(['status' => BoardingHouse::STATUS_APPROVED, 'is_verified' => true]);

        BoardingHouse::clearPublicCaches($boardingHouse->id);

        return back()->with('success', 'Listing reactivated successfully.');
    }

    private function validateBoardingHouseData(Request $request): array
    {
        return $request->validate([
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'location_description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'rent_price' => ['required', 'numeric', 'min:0'],
            'total_rooms' => ['required', 'integer', 'min:0'],
            'available_rooms' => ['required', 'integer', 'min:0'],
            'total_bedspaces' => ['required', 'integer', 'min:0'],
            'available_bedspaces' => ['required', 'integer', 'min:0'],
            'amenities_text' => ['nullable', 'string', 'max:1000'],
            'rules' => ['nullable', 'string', 'max:2000'],
            'allowed_genders' => ['nullable', 'string', 'max:255'],
            'includes_water' => ['nullable', 'boolean'],
            'includes_electricity' => ['nullable', 'boolean'],
            'water_billing_details' => ['nullable', 'string', 'max:255'],
            'electricity_billing_details' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;
        while (BoardingHouse::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . (++$counter);
        }
        return $slug;
    }
}