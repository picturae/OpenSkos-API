<?php

declare(strict_types=1);

namespace App\Rdf\Format;

use App\Utils\SingletonTrait;

final class Turtle implements RdfFormat
{
    use SingletonTrait;

    public function name(): string
    {
        return 'ttl';
    }

    public function contentTypeString(): string
    {
        return 'text/turtle';
    }
}
