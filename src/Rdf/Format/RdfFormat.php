<?php

declare(strict_types=1);

namespace App\Rdf\Format;

interface RdfFormat
{
    public function name(): string;

    public function contentTypeString(): string;
}
