<?php

declare(strict_types=1);

namespace App\Rdf;

interface RdfResource
{
    public function iri(): Iri;

    /**
     * @return Triple[]
     */
    public function triples(): array;
}
