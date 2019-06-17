<?php

declare(strict_types=1);

namespace App\Rdf\Format;

use App\Utils\SingletonTrait;

final class RdfXml implements RdfFormat
{
    use SingletonTrait;

    public function name(): string
    {
        return 'rdf';
    }

    public function contentTypeString(): string
    {
        return 'application/rdf+xml';
    }
}
