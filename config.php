<?php

namespace app\speedframework;

use app\speedframework\src\controllers\LeapYearController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();
$routes->add('hello', new Route('/hello/{name}', ['name' => 'World']));
$routes->add('bye', new Route('/bye'));
$routes->add('leap_year', new Route('/is_leap_year/{year}', [
    'year' => null,
//    '_controller' => 'LeapYearController::index',
    '_controller' => LeapYearController::class.'::index',
]));

return $routes;