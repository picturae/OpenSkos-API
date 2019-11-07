<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Trait SingletonTrait.
 */
trait SingletonTrait
{
    private function __construct()
    {
    }

    /**
     * @psalm-suppress InvalidNullableReturnType
     */
    public static function instance()
    {
        static $cache = [];
        if (!isset($cache[static::class])) {
            $cache[static::class] = new static();
        }

        return $cache[static::class];
    }
}
