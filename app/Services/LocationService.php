<?php

namespace App\Services;

class LocationService
{
    /**
     * TPC Campus Coordinates (Talibon Polytechnic College)
     */
    public const TPC_LATITUDE = 10.1167;
    public const TPC_LONGITUDE = 124.2833;

    /**
     * Standard walking speed in km/h
     */
    public const WALKING_SPEED_KMH = 4.8;

    /**
     * Calculate Haversine distance in kilometers between two coordinates
     */
    public function calculateDistanceInKilometers(
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

    /**
     * Calculate estimated walking minutes from TPC Campus
     */
    public function calculateWalkingMinutesFromTpc(float $latitude, float $longitude): int
    {
        $distance = $this->calculateDistanceInKilometers(
            self::TPC_LATITUDE,
            self::TPC_LONGITUDE,
            $latitude,
            $longitude
        );

        return max(1, (int) round(($distance / self::WALKING_SPEED_KMH) * 60));
    }
}
