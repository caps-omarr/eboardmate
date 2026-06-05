<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use Illuminate\Support\Facades\Cache; // 🚀 1. Import the Cache facade
use Inertia\Inertia;
use Inertia\Response;

class PublicMapController extends Controller
{
    public function __invoke(): Response
    {
        $tpcLatitude = 10.1167;
        $tpcLongitude = 124.2833;

        // 🚀 2. Wrap the entire query AND the mapping logic in Cache::remember.
        // We must pass $tpcLatitude and $tpcLongitude into the closure using 'use'.
        $boardingHouses = Cache::remember('public_map_markers', 1440, function () use ($tpcLatitude, $tpcLongitude) {
            return BoardingHouse::query()
                ->with('primaryPhoto')
                ->where('status', BoardingHouse::STATUS_APPROVED)
                ->where('is_verified', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->orderBy('name')
                ->get()
                ->map(function (BoardingHouse $boardingHouse) use ($tpcLatitude, $tpcLongitude) {
                    return [
                        'id' => $boardingHouse->id,
                        'name' => $boardingHouse->name,
                        'slug' => $boardingHouse->slug,
                        'rent_price' => (float) $boardingHouse->rent_price,
                        'available_rooms' => $boardingHouse->available_rooms,
                        'available_bedspaces' => $boardingHouse->available_bedspaces,
                        'latitude' => (float) $boardingHouse->latitude,
                        'longitude' => (float) $boardingHouse->longitude,
                        'is_verified' => $boardingHouse->is_verified,
                        'is_full' => $boardingHouse->isFull(),
                        'estimated_distance_km' => $this->calculateDistanceInKilometers(
                            $tpcLatitude,
                            $tpcLongitude,
                            (float) $boardingHouse->latitude,
                            (float) $boardingHouse->longitude
                        ),
                        'primary_photo_url' => $boardingHouse->primaryPhoto?->url,
                        'detail_url' => url('/boarding-houses/' . $boardingHouse->slug),
                    ];
                })
                ->values();
        });

        return Inertia::render('Public/Map', [
            'boardingHouses' => $boardingHouses,
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