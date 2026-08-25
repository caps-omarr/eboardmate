<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Services\LocationService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PublicBoardingHouseController extends Controller
{
    public function __construct(
        protected LocationService $locationService
    ) {}

    public function index(\Illuminate\Http\Request $request): Response
    {
        $genderFilter = $request->query('gender');
        $budgetFilter = $request->query('budget');

        // 🚀 OPTIMIZATION: Cache public listing index for 5 minutes
        $boardingHouses = Cache::remember('public_boarding_houses_index', now()->addMinutes(5), function () {
            return BoardingHouse::with(['photos' => function ($query) {
                $query->where('is_primary', true);
            }])
                ->where('status', 'approved')
                ->get()
                ->map(function ($house) {
                    $lat = (float) $house->latitude;
                    $lng = (float) $house->longitude;

                    $distance = $this->locationService->calculateDistanceInKilometers(
                        LocationService::TPC_LATITUDE,
                        LocationService::TPC_LONGITUDE,
                        $lat,
                        $lng
                    );

                    $walkingMinutes = $this->locationService->calculateWalkingMinutesFromTpc($lat, $lng);

                    return [
                        'id' => $house->id,
                        'name' => $house->name,
                        'slug' => $house->slug,
                        'address' => $house->address,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'rent_price' => (float) $house->rent_price,
                        'status' => $house->status,
                        'available_rooms' => $house->available_rooms,
                        'allowed_genders' => $house->allowed_genders ?? 'Any Gender (All)',
                        'includes_water' => (bool) $house->includes_water,
                        'includes_electricity' => (bool) $house->includes_electricity,
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
        });

        // 🚀 Dynamic Quick Setup Filter Application
        if ($genderFilter && $genderFilter !== 'all') {
            $boardingHouses = $boardingHouses->filter(function ($house) use ($genderFilter) {
                if (empty($house['allowed_genders']) || $house['allowed_genders'] === 'Any Gender (All)') {
                    return true;
                }
                return strtolower($house['allowed_genders']) === strtolower($genderFilter);
            })->values();
        }

        if ($budgetFilter && $budgetFilter !== 'all') {
            $budgetVal = (float) $budgetFilter;
            $boardingHouses = $boardingHouses->filter(function ($house) use ($budgetVal) {
                return (float) $house['rent_price'] <= $budgetVal || ($budgetVal >= 1000 && (float) $house['rent_price'] >= 1000);
            })->values();
        }

        return Inertia::render('Public/BoardingHousesIndex', [
            'boardingHouses' => $boardingHouses,
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
                        ->orderBy('id');
                },
            ]);

            $lat = (float) $boardingHouse->latitude;
            $lng = (float) $boardingHouse->longitude;

            $distance = $this->locationService->calculateDistanceInKilometers(
                LocationService::TPC_LATITUDE,
                LocationService::TPC_LONGITUDE,
                $lat,
                $lng
            );

            $walkingMinutes = $this->locationService->calculateWalkingMinutesFromTpc($lat, $lng);

            return [
                'id' => $boardingHouse->id,
                'name' => $boardingHouse->name,
                'slug' => $boardingHouse->slug,
                'description' => $boardingHouse->description,
                'location_description' => $boardingHouse->location_description,
                'address' => $boardingHouse->address,
                'latitude' => $lat,
                'longitude' => $lng,
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
                'status' => $boardingHouse->status,
                'is_verified' => $boardingHouse->is_verified,
                'is_full' => $boardingHouse->isFull(),
                'has_available_slot' => $boardingHouse->hasAvailableSlot(),
                'estimated_distance_km' => $this->locationService->calculateDistanceInKilometers(
                    LocationService::TPC_LATITUDE,
                    LocationService::TPC_LONGITUDE,
                    $lat,
                    $lng
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
}