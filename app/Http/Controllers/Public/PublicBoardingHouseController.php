<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use Inertia\Inertia;
use Inertia\Response;

class PublicBoardingHouseController extends Controller
{
    public function show(BoardingHouse $boardingHouse): Response
    {
        abort_unless($boardingHouse->isPubliclyVisible(), 404);

        $boardingHouse->load([
            'photos' => function ($query) {
                $query->orderByDesc('is_primary')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->limit(5);
            },
        ]);

        $tpcLatitude = 10.1167;
        $tpcLongitude = 124.2833;

        return Inertia::render('Public/BoardingHouseDetail', [
            'boardingHouse' => [
                'id' => $boardingHouse->id,
                'name' => $boardingHouse->name,
                'slug' => $boardingHouse->slug,
                'description' => $boardingHouse->description,
                'location_description' => $boardingHouse->location_description,
                'address' => $boardingHouse->address,
                'latitude' => (float) $boardingHouse->latitude,
                'longitude' => (float) $boardingHouse->longitude,
                'rent_price' => (float) $boardingHouse->rent_price,
                'total_rooms' => $boardingHouse->total_rooms,
                'available_rooms' => $boardingHouse->available_rooms,
                'total_bedspaces' => $boardingHouse->total_bedspaces,
                'available_bedspaces' => $boardingHouse->available_bedspaces,
                'amenities' => $boardingHouse->amenities ?? [],
                'rules' => $boardingHouse->rules,
                'status' => $boardingHouse->status,
                'is_verified' => $boardingHouse->is_verified,
                'is_full' => $boardingHouse->isFull(),
                'has_available_slot' => $boardingHouse->hasAvailableSlot(),
                'estimated_distance_km' => $this->calculateDistanceInKilometers(
                    $tpcLatitude,
                    $tpcLongitude,
                    (float) $boardingHouse->latitude,
                    (float) $boardingHouse->longitude
                ),
                'photos' => $boardingHouse->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => $photo->url,
                        'alt_text' => $photo->alt_text,
                        'is_primary' => $photo->is_primary,
                    ];
                })->values(),
            ],
        ]);
    }

    private function calculateDistanceInKilometers(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude
    ): float {
        $earthRadiusKilometers = 6371;

        $latitudeDifference = deg2rad($toLatitude - $fromLatitude);
        $longitudeDifference = deg2rad($toLongitude - $fromLongitude);

        $calculation = sin($latitudeDifference / 2) * sin($latitudeDifference / 2)
            + cos(deg2rad($fromLatitude))
            * cos(deg2rad($toLatitude))
            * sin($longitudeDifference / 2)
            * sin($longitudeDifference / 2);

        $centralAngle = 2 * atan2(sqrt($calculation), sqrt(1 - $calculation));

        return round($earthRadiusKilometers * $centralAngle, 2);
    }
}