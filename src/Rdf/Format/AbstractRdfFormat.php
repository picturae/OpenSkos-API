<?php

declare(strict_types=1);

namespace App\Rdf\Format;

use App\Utils\SingletonTrait;

abstract class AbstractRdfFormat implements RdfFormat
{
    use SingletonTrait;

    public function easyRdfName(): string
    {
        return $this->name();
    }
}
