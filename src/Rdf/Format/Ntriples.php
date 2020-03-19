<?php

declare(strict_types=1);

namespace App\Rdf\Format;

final class Ntriples extends AbstractRdfFormat
{
    public function name(): string
    {
        return 'n-triples';
    }

    public function contentTypeString(): string
    {
        return 'application/n-triples';
    }
}
