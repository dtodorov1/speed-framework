<?php

namespace SpeedFramework\Core\Controllers;

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
}