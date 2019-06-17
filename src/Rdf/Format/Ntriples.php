<?php

declare(strict_types=1);

namespace App\Rdf\Format;

use App\Utils\SingletonTrait;

final class Ntriples implements RdfFormat
{
    use SingletonTrait;

    public function name(): string
    {
        return 'n-triples';
    }

    public function contentTypeString(): string
    {
        return 'application/n-triples';
    }
}
