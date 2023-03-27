<?php

namespace SpeedFramework\Core;

use SpeedFramework\Core\RouterCollection;
use Symfony\Component\Routing\RouteCollection;

class RoutersRegister
{
    private RouteCollection $routeCollection;

    public function __construct()
    {
        $this->routeCollection = new RouteCollection();
    }

    public function register(RouterCollection $routerCollection)
    {
        $this->routeCollection->addCollection($routerCollection->getRouteCollection());
    }

    public function getAllRegistered()
    {
        return $this->routeCollection;
    }

}