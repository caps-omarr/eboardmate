<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BoardingHouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OwnerListingController extends Controller
{
    public function edit(Request $request): Response
    {
        $boardingHouse = BoardingHouse::query()
            ->where('owner_id', $request->user()->id)
            ->with([
                'photos' => function ($query) {
                    $query->orderByDesc('is_primary')
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
            ])
            ->first();

        return Inertia::render('Owner/Listing/Edit', [
            'boardingHouse' => $boardingHouse ? [
                'id' => $boardingHouse->id,
                'name' => $boardingHouse->name,
                'slug' => $boardingHouse->slug,
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
                'status' => $boardingHouse->status,
                'is_verified' => $boardingHouse->is_verified,
                'rejection_reason' => $boardingHouse->rejection_reason,
                'deactivated_reason' => $boardingHouse->deactivated_reason,
                'photos' => $boardingHouse->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => $photo->url,
                        'alt_text' => $photo->alt_text,
                        'is_primary' => $photo->is_primary,
                        'original_name' => $photo->original_name,
                        'file_size' => $photo->file_size,
                        'set_primary_url' => route('owner.listing.photos.primary', $photo->id),
                        'delete_url' => route('owner.listing.photos.destroy', $photo->id),
                    ];
                })->values(),
            ] : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $boardingHouse = BoardingHouse::query()
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:3000'],
            'location_description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
            'rent_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'total_rooms' => ['required', 'integer', 'min:0', 'max:999'],
            'available_rooms' => ['required', 'integer', 'min:0', 'max:999'],
            'total_bedspaces' => ['required', 'integer', 'min:0', 'max:9999'],
            'available_bedspaces' => ['required', 'integer', 'min:0', 'max:9999'],
            'amenities_text' => ['nullable', 'string', 'max:1000'],
            'rules' => ['nullable', 'string', 'max:2000'],
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

        $amenities = collect(explode(',', $validated['amenities_text'] ?? ''))
            ->map(fn ($amenity) => trim($amenity))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $boardingHouse->update([
            'description' => $validated['description'] ?? null,
            'location_description' => $validated['location_description'] ?? null,
            'address' => $validated['address'] ?? null,
            'rent_price' => $validated['rent_price'],
            'total_rooms' => $validated['total_rooms'],
            'available_rooms' => $validated['available_rooms'],
            'total_bedspaces' => $validated['total_bedspaces'],
            'available_bedspaces' => $validated['available_bedspaces'],
            'amenities' => $amenities,
            'rules' => $validated['rules'] ?? null,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'boarding_house_id' => $boardingHouse->id,
            'action' => 'owner_listing_updated',
            'description' => 'Owner updated listing details for ' . $boardingHouse->name . '.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Boarding house listing updated successfully.');
    }
}