<?php

declare(strict_types=1);

namespace App\Institution\Sparql;

use App\Institution\Institution;
use App\Institution\InstitutionRepository;
use App\Ontology\Org;
use App\Rdf\Client;
use App\Rdf\Iri;
use App\Rdf\SparqlQueryBuilder;

final class SparqlInstitutionRepository implements InstitutionRepository
{
    /**
     * @var Client
     */
    private $rdfClient;

    public function __construct(
        Client $rdfClient
    ) {
        $this->rdfClient = $rdfClient;
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return Institution[]
     */
    public function all(int $offset = 0, int $limit = 100): array
    {
        $sparql = SparqlQueryBuilder::describeAllOfType(
            new Iri(Org::FORMALORG),
            $offset,
            $limit
        );
        $triples = $this->rdfClient->describe($sparql);

        //TODO: Move to separate helper class?
        $groups = [];
        foreach ($triples as $triple) {
            $groups[$triple->getSubject()->getUri()][] = $triple;
        }

        $res = [];
        foreach ($groups as $iriString => $group) {
            $res[] = Institution::fromTriples(new Iri($iriString), $group);
        }

        return $res;
    }

    public function find(Iri $iri): ?Institution
    {
        throw new \RuntimeException('Not implemented');
    }

    public function findBy(Iri $rdfType, Iri $predicate, string $object): array
    {
        $sparql = SparqlQueryBuilder::describeByTypeAndPredicate(
            $rdfType,
            $predicate,
            $object
        );
        $triples = $this->rdfClient->describe($sparql);

        //TODO: Move to separate helper class?
        $groups = [];
        foreach ($triples as $triple) {
            $groups[$triple->getSubject()->getUri()][] = $triple;
        }

        $res = [];
        foreach ($groups as $iriString => $group) {
            $res[] = Institution::fromTriples(new Iri($iriString), $group);
        }

        return $res;
    }

    /**
     * @param Iri $rdfType
     * @param Iri $predicate
     * @param string $object
     * @return Institution|null
     */
    public function findOneBy(Iri $rdfType, Iri $predicate, string $object): ?Institution
    {
        $objects = $this->findBy($rdfType, $predicate, $object);
        if ($objects) {
            return $objects[0];
        }

        return null;
    }

}
