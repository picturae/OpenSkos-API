<?php

declare(strict_types=1);

namespace App\Rdf\Sparql;

use App\Ontology\Rdf;
use App\Rdf\Iri;

final class SparqlQuery
{
    /**
     * @var string
     */
    private $sparql;
    /**
     * @var array
     */
    private $variables;

    public function __construct(
        string $sparql,
        array $variables = []
    ) {
        $this->sparql = $sparql;
        $this->variables = $variables;
    }

    public function rawSparql(): string
    {
        //TODO: replace variables?
        return $this->sparql;
    }

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

    /**
     * @param Iri $rdfType
     * @param Iri $predicate
     * @param $object
     *
     * @return SparqlQuery
     */
    public static function describeByTypeAndPredicate(
        Iri $rdfType,
        Iri $predicate,
        $object
    ): SparqlQuery {
        $queryString = <<<QUERY_BY_TYPE_AND_PREDICATE
DESCRIBE ?subject 
    WHERE {
      ?subject <%s> <%s>;
        <%s> "%s"
    }
QUERY_BY_TYPE_AND_PREDICATE;

        $queryString = sprintf($queryString, Rdf::TYPE, (string) $rdfType, (string) $predicate, $object);

        $retVal = new SparqlQuery($queryString);

        return $retVal;
    }
}
