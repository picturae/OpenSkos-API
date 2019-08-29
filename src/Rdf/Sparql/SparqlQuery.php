<?php

declare(strict_types=1);

namespace App\Rdf\Sparql;

use App\Ontology\Rdf;
use App\Rdf\Iri;
use App\OpenSkos\Filters\FilterProcessor;

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
     * @param Iri   $type
     * @param int   $offset
     * @param int   $limit
     * @param array $filters
     *
     * @return SparqlQuery
     */
    public static function describeAllOfType(
        Iri $type,
        int $offset,
        int $limit,
        array $filters = []
    ): SparqlQuery {
        //In the interests of performance, we split out the predicates. Using filters referencing more than one string seems to put Jena in problems
        if (0 === count($filters)) {
            //If there are no filters, we can keep this simple

            $queryString = sprintf(
                'DESCRIBE ?subject WHERE { ?subject <%s> <%s> } LIMIT %d OFFSET %d',
                Rdf::TYPE,
                $type->getUri(),
                $limit,
                $offset
            );
        } else {
            //Group all filters on predicate

            /*
             * $groupedFilters is split into 3 levels
             * 1.) Entity to act upon (i.e. Institution, Set, ConceptScheme etc.
             * 2.) The predicate to act upon
             * 3.) The object values
             *
             * object predicate <-> value matches are ORed
             *
             * entity are ANDed. (Otherwise the higher group will always override the lower one
             */
            $groupedFilters = [];

            foreach ($filters as $f_key => $f_val) {
                $entity = $f_val['entity'];
                $predicate = $f_val['predicate'];

                if (!isset($groupedFilters[$entity])) {
                    $groupedFilters[$entity] = [];
                }

                if (!isset($groupedFilters[$entity][$predicate])) {
                    $groupedFilters[$entity][$predicate] = [];
                }
                $groupedFilters[$entity][$predicate][] = $f_val;
            }

            $nIdx = 0;
            $filterPredicates = [];
            $filterValues = [];

            foreach ($groupedFilters as $entity_key => $entity_val) {
                $entityValues = [];
                foreach ($entity_val as $pred_key => $pred_val) {
                    ++$nIdx;
                    $filterPredicates[] = sprintf('<%s> $f%d ', $pred_key, $nIdx);
                    foreach ($pred_val as $obj_key => $obj_val) {
                        if (FilterProcessor::TYPE_URI == $obj_val['type']) {
                            $delimOpen = '<';
                            $delimClose = '>';
                        } else {
                            $delimOpen = $delimClose = '"';
                        }
                        $entityValues[] = sprintf('$f%d = %s%s%s', $nIdx, $delimOpen, $obj_val['value'], $delimClose);
                    }
                    $filterValues[] = sprintf(' FILTER ( %s )', implode(' || ', $entityValues));
                }
            }

            $queryString = sprintf(
                'DESCRIBE ?subject WHERE{ SELECT ?subject WHERE { ?subject <%s> <%s>; %s . %s }} LIMIT %d OFFSET %d',
                Rdf::TYPE,
                $type->getUri(),
                implode('; ', $filterPredicates),
                implode(' . ', $filterValues),
                $limit,
                $offset
            );
        }

        return new SparqlQuery(
            $queryString
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

    public static function describeResources(
        array $subjects
    ): SparqlQuery {
        $uris = array_map(function ($v) { return sprintf('?subject = <%s> ', $v); }, $subjects);
        $query = sprintf('DESCRIBE ?subject WHERE {?subject ?predicate ?object . FILTER ( %s ) }', join(' || ', $uris));

        return new SparqlQuery($query);
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
