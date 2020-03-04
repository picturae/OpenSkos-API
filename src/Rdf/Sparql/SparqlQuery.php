<?php

declare(strict_types=1);

namespace App\Rdf\Sparql;

use App\Annotation\ErrorInherit;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\OpenSkos\Filters\FilterProcessor;
use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;
use App\Rdf\Literal\Literal;

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
        $this->sparql    = $sparql;
        $this->variables = $variables;
    }

    public function rawSparql(): string
    {
        //TODO: replace variables?
        return $this->sparql;
    }

    /**
     * @ErrorInherit(class=Iri::class        , method="getUri"     )
     * @ErrorInherit(class=SparqlQuery::class, method="__construct")
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
                $entity    = $f_val['entity'];
                $predicate = $f_val['predicate'];

                if (!isset($groupedFilters[$entity])) {
                    $groupedFilters[$entity] = [];
                }

                if (!isset($groupedFilters[$entity][$predicate])) {
                    $groupedFilters[$entity][$predicate] = [];
                }
                $groupedFilters[$entity][$predicate][] = $f_val;
            }

            $nIdx             = 0;
            $filterPredicates = [];
            $filterValues     = [];

            foreach ($groupedFilters as $entity_key => $entity_val) {
                $entityValues = [];
                foreach ($entity_val as $pred_key => $pred_val) {
                    ++$nIdx;
                    $filterPredicates[] = sprintf('<%s> $f%d ', $pred_key, $nIdx);
                    foreach ($pred_val as $obj_key => $obj_val) {
                        if (FilterProcessor::TYPE_URI == $obj_val['type']) {
                            $delimOpen  = '<';
                            $delimClose = '>';
                        } else {
                            $delimOpen = $delimClose = '"';
                        }
                        $entityValues[] = sprintf(
                            '$f%d = %s%s%s%s',
                            $nIdx,
                            $delimOpen,
                            $obj_val['value'],
                            $delimClose,
                            isset($obj_val['lang']) ? '@'.$obj_val['lang'] : ''
                        );
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

    /**
     * @ErrorInherit(class=Iri::class        , method="getUri"     )
     * @ErrorInherit(class=SparqlQuery::class, method="__construct")
     */
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
     * @ErrorInherit(class=Iri::class        , method="getUri"     )
     * @ErrorInherit(class=SparqlQuery::class, method="__construct")
     */
    public static function describeResourceOfType(
        Iri $rdfType,
        Iri $subject
    ): SparqlQuery {
        $query = <<<RETRIEVE_OF_TYPE
DESCRIBE ?subject
    WHERE {
       ?subject a <%s>
    }
VALUES (?subject) {(<%s>)}
RETRIEVE_OF_TYPE;

        return new SparqlQuery(
            sprintf($query, $rdfType->getUri(), $subject->getUri())
        );
    }

    /**
     * @ErrorInherit(class=SparqlQuery::class, method="__construct")
     */
    public static function describeResources(
        array $subjects
    ): SparqlQuery {
        $uris  = array_map(function ($v) { return sprintf('?subject = <%s> ', $v); }, $subjects);
        $query = sprintf('DESCRIBE ?subject WHERE {?subject ?predicate ?object . FILTER ( %s ) }', join(' || ', $uris));

        return new SparqlQuery($query);
    }

    /**
     * @param Iri|Literal|InternalResourceId|string $object
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

        return new SparqlQuery($queryString);
    }

    /**
     * @param Iri|Literal|InternalResourceId|string $object
     *
     * @ErrorInherit(class=Iri::class        , method="ntripleString")
     * @ErrorInherit(class=Literal::class    , method="__toString"   )
     * @ErrorInherit(class=SparqlQuery::class, method="__construct"  )
     */
    public static function selectSubjectFromPredicate(
        Iri $predicate,
        $object
    ): SparqlQuery {
        $queryString = <<<QUERY_SELECT_SUBJECT_FROM_PREDICATE
SELECT ?subject
WHERE {
    ?subject <%s> %s .
}
QUERY_SELECT_SUBJECT_FROM_PREDICATE;

        if ($object instanceof Iri) {
            $object = $object->ntripleString();
        }
        if ($object instanceof Literal) {
            $object = '"'.$object->__toString().'"';
        }

        $queryString = sprintf($queryString, (string) $predicate, (string) $object);

        return new SparqlQuery($queryString);
    }

    /**
     * @param Iri|Literal|InternalResourceId|string $object
     *
     * @ErrorInherit(class=Iri::class        , method="ntripleString")
     * @ErrorInherit(class=Literal::class    , method="__toString"   )
     * @ErrorInherit(class=SparqlQuery::class, method="__construct"  )
     */
    public static function selectSubjectFromObject(
        $object
    ): SparqlQuery {
        $queryString = <<<QUERY_SELECT_SUBJECT_FROM_PREDICATE
SELECT ?subject ?predicate ?type
WHERE {
    ?subject ?predicate %s .
    ?subject <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?type .
}
QUERY_SELECT_SUBJECT_FROM_PREDICATE;

        if ($object instanceof Iri) {
            $object = $object->ntripleString();
        }
        if ($object instanceof Literal) {
            $object = '"'.$object->__toString().'"';
        }

        $queryString = sprintf($queryString, (string) $object);

        return new SparqlQuery($queryString);
    }

    /**
     * @ErrorInherit(class=Iri::class        , method="__construct"               )
     * @ErrorInherit(class=SparqlQuery::class, method="__construct"               )
     * @ErrorInherit(class=SparqlQuery::class, method="selectSubjectFromPredicate")
     */
    public static function selectSubjectFromUuid(
        string $uuid
    ): SparqlQuery {
        return static::selectSubjectFromPredicate(
            new Iri(OpenSkos::UUID),
            $uuid
        );
    }

    /**
     * @ErrorInherit(class=SparqlQuery::class, method="__construct"               )
     */
    public static function describeSubjectFromUuid(
        string $uuid
    ): SparqlQuery {
        return new SparqlQuery(
            sprintf(
               'DESCRIBE ?subject
                            WHERE { 
                              ?subject <%s> "%s"
                            }',
                OpenSkos::UUID,
                $uuid
            )
        );
    }

    /**
     * @ErrorInherit(class=SparqlQuery::class, method="__construct"               )
     */
    public static function describeWithoutUUID(
        string $uuid
    ): SparqlQuery {
        /* * * * * * * * * * * * * * * * * * * * * * * * * *
         * CAUTION: SLOW ON TYPES WITH A LOT OF RESOURCES  *
        \* * * * * * * * * * * * * * * * * * * * * * * * * */

        $queryString = <<<QUERY_WITHOUT_UUID
DESCRIBE ?subject
WHERE {
  ?subject ?predicate ?object .
  FILTER(regex(str(?subject), ".*\\\\/%s\$" ) )
}
QUERY_WITHOUT_UUID;
        $queryString = sprintf($queryString, $uuid);

        return new SparqlQuery($queryString);
    }

    /**
     * @ErrorInherit(class=SparqlQuery::class, method="__construct"               )
     */
    public static function describeByTypeWithoutUUID(
        string $rdfType,
        string $uuid
    ): SparqlQuery {
        /* * * * * * * * * * * * * * * * * * * * * * * * * *
         * CAUTION: SLOW ON TYPES WITH A LOT OF RESOURCES  *
        \* * * * * * * * * * * * * * * * * * * * * * * * * */

        $queryString = <<<QUERY_BY_TYPE_WITHOUT_UUID
DESCRIBE ?subject
WHERE {
  ?subject <%s> <%s> .
  FILTER(regex(str(?subject), ".*\\\\/%s\$" ) )
}
QUERY_BY_TYPE_WITHOUT_UUID;
        $queryString = sprintf($queryString, Rdf::TYPE, $rdfType, $uuid);

        return new SparqlQuery($queryString);
    }

    /**
     * @ErrorInherit(class=SparqlQuery::class, method="__construct"               )
     */
    public static function deleteSubject(
        string $subject
    ): SparqlQuery {
        $queryString = <<<QUERY_DELETE_SUBJECT_WITH_TYPE
DELETE WHERE {
    <%s> ?predicate ?object .
}
QUERY_DELETE_SUBJECT_WITH_TYPE;
        $queryString = sprintf($queryString, $subject);

        return new SparqlQuery($queryString);
    }
}
