<?php

namespace SpeedFramework\Core\Controllers;

use app\services\CarsService;
use app\services\LocationsService;

class SiteController
{
    private CarsService $carsService;
    private LocationsService $locationsService;

    public function __construct(CarsService $carsService, LocationsService $locationsService)
    {

    }


    public function contact()
    {
        return "handling submitted data";
    }
}