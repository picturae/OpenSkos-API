<?php

declare(strict_types=1);

namespace App\Rdf\Format;

use App\Utils\SingletonTrait;

final class JsonLd implements RdfFormat
{
    use SingletonTrait;

    public function name(): string
    {
        return 'json-ld';
    }

    public function contentTypeString(): string
    {
        return 'application/rdf+json';
    }
}
