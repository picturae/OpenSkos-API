<?php

declare(strict_types=1);

namespace App\OpenSkos;

use App\OpenSkos\Concept\Concept;
use App\Rdf\Iri;
use App\Rdf\Literal\StringLiteral;
use App\Rdf\Sparql\SparqlQuery;

final class SkosResourceRepositoryWithProjection extends SkosResourceRepository
{
    public function findManyByIriListWithProjection(array $iris, array $projection): array
    {
        $sparql  = SparqlQuery::describeResources($iris);
        $triples = $this->rdfClient->describe($sparql);
        if (0 === count($triples)) {
            return [];
        }

        $fields_to_project = [];
        $do_projection     = false;
        //Extract which fields we want to use from the projection parameters
        $acceptable_fields = Concept::getAcceptableFields();
        foreach ($projection as $key => $param) {
            if (isset($acceptable_fields[$key])) {
                $do_projection                               = true;
                $fields_to_project[$acceptable_fields[$key]] = $param['lang'];
            }
        }

        //TODO: Move to separate helper class?
        $groups = [];
        foreach ($triples as $triple) {
            $predicate = $triple->getPredicate()->getUri();
            $object    = $triple->getObject();
            if (!$do_projection || isset($fields_to_project[$predicate])) {
                if (('' === $fields_to_project[$predicate]) ||
                     ($object instanceof StringLiteral && $fields_to_project[$predicate] === $object->lang())) {
                    $groups[$triple->getSubject()->getUri()][] = $triple;
                }
            }
        }

        $res = [];
        foreach ($groups as $iriString => $group) {
            $res[] = call_user_func($this->resourceFactory, new Iri($iriString), $group);
        }

        return $res;
    }
}
