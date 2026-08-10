<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PublicBoardingHouseController extends Controller
{
    public function index(): Response
    {
        // TPC Coordinates for distance calculation
        $tpcLatitude = 10.1167;
        $tpcLongitude = 124.2833;

        $boardingHouses = BoardingHouse::with(['photos' => function ($query) {
            $query->where('is_primary', true)->orWhere('is_primary', 1); 
        }])
        // 🚀 THE FIX: Only fetch boarding houses that have an 'approved' status
        ->where('status', 'approved') 
        ->get()
        ->map(function ($house) use ($tpcLatitude, $tpcLongitude) {
            $distance = $this->calculateDistanceInKilometers(
                $tpcLatitude,
                $tpcLongitude,
                (float) $house->latitude,
                (float) $house->longitude
            );
            
            // Dynamic walking minutes calculation based on standard walking speed (~4.8 km/h)
            $walkingMinutes = max(1, (int) round(($distance / 4.8) * 60));

            return [
                'id' => $house->id,
                'name' => $house->name,
                'slug' => $house->slug,
                'address' => $house->address,
                
                // We MUST send the coordinates so Vue can call Mapbox!
                'latitude' => (float) $house->latitude,
                'longitude' => (float) $house->longitude,
                
                'rent_price' => (float) $house->rent_price,
                'status' => $house->status,
                'available_rooms' => $house->available_rooms,
                'is_full' => $house->isFull(),
                'estimated_distance_km' => $distance,
                'estimated_walking_mins' => $walkingMinutes,
                'photos' => $house->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => $photo->url,
                        'is_primary' => $photo->is_primary,
                    ];
                })->values(),
            ];
        });

        return Inertia::render('Public/BoardingHousesIndex', [
            'boardingHouses' => $boardingHouses
        ]);
    }

    public function show(BoardingHouse $boardingHouse): Response
    {
        abort_unless($boardingHouse->isPubliclyVisible(), 404);

        $cacheKey = "boarding_house_public_details_{$boardingHouse->id}";

        $formattedBoardingHouse = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($boardingHouse) {
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

            return [
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
            ];
        });

        return Inertia::render('Public/BoardingHouseDetail', [
            'boardingHouse' => $formattedBoardingHouse,
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