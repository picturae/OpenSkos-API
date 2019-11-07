<?php

declare(strict_types=1);

namespace App\Rdf\Format;

final class Turtle extends AbstractRdfFormat
{
    public function name(): string
    {
        return 'ttl';
    }

    public function contentTypeString(): string
    {
        return 'text/turtle';
    }
}
