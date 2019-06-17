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
     * @var self|null
     */
    private static $obj;

    /**
     * @psalm-suppress InvalidNullableReturnType
     *
     * @return self
     */
    public static function instance(): self
    {
        if (null === self::$obj) {
            self::$obj = new self();
        }

        return self::$obj;
    }
}
