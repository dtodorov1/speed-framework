<?php

namespace App\Filestore;

use InvalidArgumentException;

class SimpleCacheItem
{
    private string $key;
    private mixed $value;
    private bool $isHit;
    public const RESERVED_CHARACTERS = '{}()/\@:';


    public function __construct(string $key, mixed $value = null, bool $isHit = false)
    {
        $this->key = $key;
        $this->value = $value;
        $this->isHit = $isHit;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    public function setIsHit($value): void
    {
        $this->isHit = $value;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function set($value)
    {
        $this->value = $value;
    }


    public static function validateKey($key): string
    {
        if (!\is_string($key)) {
            throw new InvalidArgumentException(sprintf('Cache key must be string, "%s" given.', get_debug_type($key)));
        }
        if ('' === $key) {
            throw new InvalidArgumentException('Cache key length must be greater than zero.');
        }
        if (false !== strpbrk($key, self::RESERVED_CHARACTERS)) {
            throw new InvalidArgumentException(sprintf('Cache key "%s" contains reserved characters "%s".', $key, self::RESERVED_CHARACTERS));
        }

        return $key;
    }
}