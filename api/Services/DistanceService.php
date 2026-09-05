<?php
namespace Api\Services;

class DistanceService {

    public static function kilometers($latitudeA, $longitudeA, $latitudeB, $longitudeB): ?float
    {
        if (
            !self::isCoordinate($latitudeA) ||
            !self::isCoordinate($longitudeA) ||
            !self::isCoordinate($latitudeB) ||
            !self::isCoordinate($longitudeB)
        ) {
            return null;
        }

        $earthRadius = 6371.0;
        $latA = deg2rad((float)$latitudeA);
        $latB = deg2rad((float)$latitudeB);
        $deltaLat = deg2rad((float)$latitudeB - (float)$latitudeA);
        $deltaLon = deg2rad((float)$longitudeB - (float)$longitudeA);

        $a = sin($deltaLat / 2) ** 2
            + cos($latA) * cos($latB) * sin($deltaLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 1);
    }

    private static function isCoordinate($value): bool
    {
        return $value !== null && $value !== '' && is_numeric($value);
    }
}
