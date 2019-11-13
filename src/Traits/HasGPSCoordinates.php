<?php
/**
 * Created by PhpStorm.
 * User: jchedev
 * Date: 2018-12-27
 * Time: 17:22
 */

namespace Jchedev\Laravel\Traits;

use Jchedev\Laravel\Classes\GPS\GPSCoordinates;

trait HasGPSCoordinates
{
    public function getGPSCoordinates()
    {
        return new GPSCoordinates($this->latitude, $this->longitude);
    }
}