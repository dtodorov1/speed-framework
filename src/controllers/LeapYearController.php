<?php

namespace SpeedFramework\Core\Controllers;

use SpeedFramework\Core\Cache\Filesystem\FilesystemAdapter;
use SpeedFramework\Core\Models\Location;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LeapYearController
{
    public function index($year)
    {
        if (($year % 4) === 0 && ($year % 100) != 0) {
            $response = new Response('Yep, this is a leap year!' . rand());
        } else {
            $response = new Response('Nope, this is not a leap year.');
        }

        $response->setTtl(10);

        return $response;
    }

    public function get()
    {
        $cache = new FilesystemAdapter();
        $cachedData = $cache->getItem('years');

        if (!$cachedData->isHit()) {
            $res = new Response('az sum hermafrodit');
        } else {
            $res = new Response($cachedData->get());
        }

        return $res;
    }

    public function set()
    {
        $location = new Location('Example Name', 'Example Description', 15, 40);

        $cache = new FilesystemAdapter();
        $cachedData = $cache->getItem('locations');

        if (!$cachedData->isHit()) {
            $data = $location;
            $cachedData->set($data);
            $cache->save($cachedData);
            return new Response('not hit ' . $data->getName());
        }

        $data = $cachedData->get();
        return new Response('hit ' . $data->getName());
    }
}