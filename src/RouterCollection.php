<?php

namespace SpeedFramework\Core;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouterCollection
{
    private RouteCollection $routes;


    public function __construct()
    {
        $this->routes = new RouteCollection();
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->routes;
    }

    public function get(string $path = '', string $controller = '', string $name = ''): void
    {
        $this->routes->add($name, new Route($path, ['_controller' => $controller]));
    }

    public function post(string $path = '', string $controller = '', string $name = ''): void
    {
        $this->routes->add($name, new Route($path, ['_controller' => $controller]));
    }
}