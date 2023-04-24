<?php

namespace SpeedFramework\Core\Models;


class Location
{
    private string $name;
    private string $desc;
    private int $lat;
    private int $long;

    public function __construct($name, $desc, $lat, $long)
    {
        $this->name = $name;
        $this->desc = $desc;
        $this->lat = $lat;
        $this->long = $long;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDesc(): string
    {
        return $this->desc;
    }

    public function getLat(): int
    {
        return $this->lat;
    }

    public function getLong(): int
    {
        return $this->long;
    }
}