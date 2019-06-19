<?php

declare(strict_types=1);

namespace App\Rdf\Sparql;

use App\Rdf\Triple;

interface Client
{
    /**
     * @param SparqlQuery $query
     *
     * @return Triple[]
     */
    public function describe(SparqlQuery $query): array;
}
