<?php

declare(strict_types=1);

namespace App\Rdf\Format;

final class JsonLd extends AbstractRdfFormat
{
    public function easyRdfName(): string
    {
        return 'jsonld';
    }

    public function name(): string
    {
        return 'json-ld';
    }

    public function contentTypeString(): string
    {
        return 'application/rdf+json';
    }
}
