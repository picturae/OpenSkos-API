<?php

declare(strict_types=1);

namespace App\Rdf\Sparql;

use App\Rdf\Triple;

interface Client
{
    /**
     * @return Triple[]
     */
    public function describe(SparqlQuery $query): array;

    /**
     * @param Triple[] $triples
     */
    public function insertTriples(array $triples): \EasyRdf_Http_Response;
}
