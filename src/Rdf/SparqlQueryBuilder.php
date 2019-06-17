<?php

declare(strict_types=1);

namespace App\Rdf;

use App\Ontology\Rdf;

final class SparqlQueryBuilder
{
    /**
     * FIXME: Make it not static.
     *
     * @param Iri $type
     * @param int $offset
     * @param int $limit
     *
     * @return SparqlQuery
     */
    public static function describeAllOfType(
        Iri $type,
        int $offset,
        int $limit
    ): SparqlQuery {
        return new SparqlQuery(
            sprintf(
                'DESCRIBE ?x WHERE { ?x <%s> <%s> } LIMIT %d OFFSET %d',
                Rdf::TYPE,
                $type->getUri(),
                $limit,
                $offset
            )
        );
    }

    public static function describeResource(
        Iri $subject
    ): SparqlQuery {
        return new SparqlQuery(
            sprintf(
                'DESCRIBE <%s>', $subject->getUri()
            )
        );
    }
}
