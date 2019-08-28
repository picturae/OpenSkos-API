<?php

declare(strict_types=1);

namespace App\Rdf;

interface RdfResource
{
    public function iri(): Iri;

    /**
     * @return array
     */
    public function triples(): array;
}
