<?php

declare(strict_types=1);

namespace App\Rdf;

use App\Ontology\Rdf;

final class SparqlQueryBuilder
{
    public static function describeAllOfType(
        Iri $type,
        int $offset,
        int $limit
    ): SparqlQuery {
        return new SparqlQuery(
            sprintf(
                'DESCRIBE ?x WHERE { ?x <%s> <%s> } LIMIT %d OFFSET %d',
                Rdf::TYPE,
                $type,
                $limit,
                $offset
            )
        );
    }
}
