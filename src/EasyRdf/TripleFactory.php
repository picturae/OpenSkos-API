<?php

declare(strict_types=1);

namespace App\EasyRdf;

use App\Rdf\Iri;
use App\Rdf\Literal\BooleanLiteral;
use App\Rdf\Literal\DatetimeLiteral;
use App\Rdf\Literal\StringLiteral;
use App\Rdf\RdfTerm;
use App\Rdf\Triple;
use EasyRdf_Graph;

final class TripleFactory
{
    /**
     * @param array $arr
     *
     * @return RdfTerm|null
     */
    private static function arrayToRdfTerm(array $arr): ?RdfTerm
    {
        $type = $arr['type'];
        $value = $arr['value'];
        if (null === $type || null === $value) {
            return null;
        }

        switch ($type) {
            case 'uri':
                return new Iri($value);
            case 'literal':
                // FIXME: Possible performance issues
                switch ($arr['datatype'] ?? null) {
                    case BooleanLiteral::typeIri()->getUri(): return BooleanLiteral::fromString($value);
                    case DatetimeLiteral::typeIri()->getUri(): return DatetimeLiteral::fromString($value);
                    default: return new StringLiteral($value, $arr['lang'] ?? null);
                }
        }

        return null;
    }

    /**
     * @param EasyRdf_Graph $graph
     *
     * @return Triple[]
     */
    public static function triplesFromGraph(EasyRdf_Graph $graph): array
    {
        $resources = $graph->toRdfPhp();

        $res = [];
        foreach ($resources as $subject => $predicates) {
            $subjectIri = new Iri($subject);
            foreach ($predicates as $predicate => $objects) {
                $predicateIri = new Iri($predicate);
                foreach ($objects as $object) {
                    $rdfTerm = self::arrayToRdfTerm($object);
                    if (null === $rdfTerm) {
                        // TODO: Throw an exception?
                        continue;
                    }
                    $res[] = new Triple($subjectIri, $predicateIri, $rdfTerm);
                }
            }
        }

        return $res;
    }
}
