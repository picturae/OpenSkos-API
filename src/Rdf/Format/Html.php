<?php

declare(strict_types=1);

namespace App\Rdf\Format;

use App\Utils\SingletonTrait;

final class Html implements RdfFormat
{
    use SingletonTrait;

    public function name(): string
    {
        return 'html';
    }

    public function contentTypeString(): string
    {
        return 'text/html';
    }
}
