<?php

namespace Jchedev\Laravel\Classes\GPS;

/**
 * Represents a GPS point (longitude + latitude) and could be extended in multiple ways
 */
class GPSCoordinates
{
    protected $latitude;

    protected $longitude;

    /**
     * GPSCoordinates constructor.
     *
     * @param null $latitude
     * @param null $longitude
     */
    public function __construct($latitude = null, $longitude = null)
    {
        $this->latitude = $latitude;

        $this->longitude = $longitude;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * @return null
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @return null
     */
    public function getLongitude()
    {
        return $this->longitude;
    }
}