<?php

declare(strict_types=1);

namespace App\OpenSkos;

final class InternalResourceId
{
    /**
     * @var string
     */
    private $id;

    public function __construct(
        string $id
    ) {
        $this->id = $id;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return "$this->id";
    }

    public function isEquals(self $that): bool
    {
        return $this->id === $that->id;
    }
}
