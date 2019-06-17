<?php

declare(strict_types=1);

namespace App\Rdf\Format;

final class UnknownFormatException extends \Exception
{
    /**
     * @param string $format
     *
     * @return UnknownFormatException
     */
    public static function create(string $format): self
    {
        return new self("Unknown format name: '$format'");
    }
}
