<?php

declare(strict_types=1);

namespace App\EasyRdf;

use App\Annotation\ErrorInherit;
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
     * @ErrorInherit(class=BooleanLiteral::class , method="fromString" )
     * @ErrorInherit(class=BooleanLiteral::class , method="typeIri"    )
     * @ErrorInherit(class=DatetimeLiteral::class, method="fromString" )
     * @ErrorInherit(class=DatetimeLiteral::class, method="typeIri"    )
     * @ErrorInherit(class=Iri::class            , method="__construct")
     * @ErrorInherit(class=Iri::class            , method="getUri"     )
     * @ErrorInherit(class=StringLiteral::class  , method="__construct")
     */
    private static function arrayToRdfTerm(array $arr): ?RdfTerm
    {
        $type  = $arr['type'];
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
     * @ErrorInherit(class=Iri::class          , method="__construct"   )
     * @ErrorInherit(class=Triple::class       , method="__construct"   )
     * @ErrorInherit(class=TripleFactory::class, method="arrayToRdfTerm")
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
