<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BoardingHouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
                'business_permit_url' => $boardingHouse->business_permit_url,
                'is_permit_processing' => (bool) $boardingHouse->is_permit_processing,
                'permit_processing_notes' => $boardingHouse->permit_processing_notes,
                'house_rules_image_url' => $boardingHouse->house_rules_image_url,
                'allowed_genders' => $boardingHouse->allowed_genders ?? 'Any Gender (All)',
                'includes_water' => (bool) $boardingHouse->includes_water,
                'includes_electricity' => (bool) $boardingHouse->includes_electricity,
                'water_billing_details' => $boardingHouse->water_billing_details,
                'electricity_billing_details' => $boardingHouse->electricity_billing_details,
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
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'rent_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'total_rooms' => ['required', 'integer', 'min:0', 'max:999'],
            'available_rooms' => ['required', 'integer', 'min:0', 'max:999'],
            'total_bedspaces' => ['required', 'integer', 'min:0', 'max:9999'],
            'available_bedspaces' => ['required', 'integer', 'min:0', 'max:9999'],
            'amenities_text' => ['nullable', 'string', 'max:1000'],
            'rules' => ['nullable', 'string', 'max:2000'],
            'is_permit_processing' => ['nullable', 'boolean'],
            'permit_processing_notes' => ['nullable', 'string', 'max:2000'],
            'business_permit' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:15360'],
            'house_rules_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'remove_business_permit' => ['nullable', 'boolean'],
            'remove_house_rules_image' => ['nullable', 'boolean'],
            'allowed_genders' => ['nullable', 'string', 'max:255'],
            'includes_water' => ['nullable', 'boolean'],
            'includes_electricity' => ['nullable', 'boolean'],
            'water_billing_details' => ['nullable', 'string', 'max:255'],
            'electricity_billing_details' => ['nullable', 'string', 'max:255'],
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

        $includesWater = (bool) ($validated['includes_water'] ?? false);
        $includesElectricity = (bool) ($validated['includes_electricity'] ?? false);

        $waterBillingDetails = $includesWater ? null : ($validated['water_billing_details'] ?? null);
        $electricityBillingDetails = $includesElectricity ? null : ($validated['electricity_billing_details'] ?? null);

        $amenities = collect(explode(',', $validated['amenities_text'] ?? ''))
            ->map(fn ($amenity) => trim($amenity))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $targetDisk = config('filesystems.default') === 'cloudinary' ? 'cloudinary' : 'public';

        // Business Permit handling
        $businessPermitUrl = $boardingHouse->business_permit_url;
        if ($request->boolean('remove_business_permit')) {
            $businessPermitUrl = null;
        }
        if ($request->hasFile('business_permit')) {
            $permitFile = $request->file('business_permit');
            $permitExt = $permitFile->getClientOriginalExtension() ?: 'jpg';
            $permitName = Str::uuid() . '.' . $permitExt;
            $permitPath = 'boarding-houses/' . $boardingHouse->id . '/permits/' . $permitName;

            Storage::disk($targetDisk)->putFileAs(
                dirname($permitPath),
                $permitFile,
                basename($permitPath)
            );

            $businessPermitUrl = ($targetDisk === 'cloudinary')
                ? Storage::disk('cloudinary')->url($permitPath)
                : asset('storage/' . $permitPath);
        }

        // House Rules photo handling
        $houseRulesImageUrl = $boardingHouse->house_rules_image_url;
        if ($request->boolean('remove_house_rules_image')) {
            $houseRulesImageUrl = null;
        }
        if ($request->hasFile('house_rules_image')) {
            $rulesFile = $request->file('house_rules_image');
            $rulesExt = $rulesFile->getClientOriginalExtension() ?: 'jpg';
            $rulesName = Str::uuid() . '.' . $rulesExt;
            $rulesPath = 'boarding-houses/' . $boardingHouse->id . '/rules/' . $rulesName;

            Storage::disk($targetDisk)->putFileAs(
                dirname($rulesPath),
                $rulesFile,
                basename($rulesPath)
            );

            $houseRulesImageUrl = ($targetDisk === 'cloudinary')
                ? Storage::disk('cloudinary')->url($rulesPath)
                : asset('storage/' . $rulesPath);
        }

        $isPermitProcessing = (bool) ($validated['is_permit_processing'] ?? false);
        $permitProcessingNotes = $isPermitProcessing ? ($validated['permit_processing_notes'] ?? null) : null;

        $boardingHouse->update([
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
            'business_permit_url' => $businessPermitUrl,
            'is_permit_processing' => $isPermitProcessing,
            'permit_processing_notes' => $permitProcessingNotes,
            'house_rules_image_url' => $houseRulesImageUrl,
            'allowed_genders' => $validated['allowed_genders'] ?? 'Any Gender (All)',
            'includes_water' => $includesWater,
            'includes_electricity' => $includesElectricity,
            'water_billing_details' => $waterBillingDetails,
            'electricity_billing_details' => $electricityBillingDetails,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'boarding_house_id' => $boardingHouse->id,
            'action' => 'owner_listing_updated',
            'description' => 'Owner updated listing details for ' . $boardingHouse->name . '.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        BoardingHouse::clearPublicCaches($boardingHouse->id);

        return back()->with('success', 'Boarding house listing updated successfully.');
    }
}