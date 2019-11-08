<?php

declare(strict_types=1);

namespace App\Rdf\Format;

final class RdfXml extends AbstractRdfFormat
{
    public function name(): string
    {
        return 'rdf';
    }

    public function contentTypeString(): string
    {
        return 'application/rdf+xml';
    }
}
