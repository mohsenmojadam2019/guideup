<?php

namespace App\Traits;

trait CoordinateTrait
{

	public $earthRadiusKm = 3963.19;
	public $earthRadiusMi = 6378.137;
	public $earthScaleFactor = 3.14159 / 180; // scaling factor
	/**
	 * calculate destination lat/lng given a starting point, bearing, and distance
	 *
	 * @param \float $lat Latitude
	 * @param \float $lng Longitude
	 * @param \integer $distance Distance
	 * @param \string $units Units: default km. Any other value will result in computing with mile based constants.
	 * @return \array An array with lat and lng values
	 * @codeCoverageIgnore
	 */
	public function destination($lat,$lng, $bearing, $distance, $units='km') 
    {
        $radius = strcasecmp($units, 'km') ? $this->earthRadiusKm : $this->earthRadiusMi;
        $rLat = deg2rad($lat);
        $rLon = deg2rad($lng);
        $rBearing = deg2rad($bearing);
        $rAngDist = $distance / $radius;
        $rLatB = asin(sin($rLat) * cos($rAngDist) + 
            cos($rLat) * sin($rAngDist) * cos($rBearing));
        $rLonB = $rLon + atan2(sin($rBearing) * sin($rAngDist) * cos($rLat), 
                            cos($rAngDist) - sin($rLat) * sin($rLatB));
        return array('lat' => rad2deg($rLatB), 'lng' => rad2deg($rLonB));
	}
	
	/**
	 * calculate bounding box
	 *
	 * @param \float $lat Latitude of location
	 * @param \float $lng Longitude of location
	 * @param \float $distance Distance around location
	 * @param \string $units Unit: default km. Any other value will result in computing with mile based constants.
	 * @return \array An array describing a bounding box
	 * @codeCoverageIgnore
	 */
	public function getBoundsByRadius($lat, $lng, $distance, $units='km') 
    {
		return array('N' => $this->destination($lat,$lng, 0, $distance, $units),
			'E' => $this->destination($lat,$lng, 90, $distance, $units),
			'S' => $this->destination($lat,$lng, 180, $distance, $units),
			'W' => $this->destination($lat,$lng, 270, $distance, $units));
	}

	/**
	 * calculate distance between two lat/lon coordinates
	 *
	 * @param \float $latA Latitude of location A
	 * @param \float $lonA Longitude of location A
	 * @param \float $latB Latitude of location B
	 * @param \float $lonB Longitude of location B
	 * @param \string $units Units: default km. Any other value will result in computing with mile based constants.
	 * @return \float
	 * @codeCoverageIgnore
	 */
	public function distance($latA,$lonA, $latB,$lonB, $units='km') 
    {
        $radius = strcasecmp($units, 'km') ? $this->earthRadiusKm : $this->earthRadiusMi;
		$rLatA = deg2rad($latA);
		$rLatB = deg2rad($latB);
		$rHalfDeltaLat = deg2rad(($latB - $latA) / 2);
		$rHalfDeltaLon = deg2rad(($lonB - $lonA) / 2);
		return 2 * $radius * asin(sqrt(pow(sin($rHalfDeltaLat), 2) +
					cos($rLatA) * cos($rLatB) * pow(sin($rHalfDeltaLon), 2)));
	}
}