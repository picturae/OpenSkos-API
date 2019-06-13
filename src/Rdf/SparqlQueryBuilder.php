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

    public static function describeByTypeAndPredicate(
        Iri $rdfType,
        Iri $predicate,
        string $object
    ): SparqlQuery {
        $queryString = <<<QUERY_BY_TYPE_AND_PREDICATE
DESCRIBE ?subject 
    WHERE {
      ?subject <%s> <%s>;
        <%s> "%s"
    }
QUERY_BY_TYPE_AND_PREDICATE;

        $queryString = sprintf($queryString, Rdf::TYPE, $rdfType, $predicate, $object);

        $retVal = new SparqlQuery($queryString);

        return $retVal;
    }
}
