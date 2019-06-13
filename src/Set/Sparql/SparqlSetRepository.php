<?php

declare(strict_types=1);

namespace App\Set\Sparql;

use App\Set\Set;
use App\Set\SetRepository;
use App\Ontology\OpenSkos;
use App\Rdf\Client;
use App\Rdf\Iri;
use App\Rdf\SparqlQueryBuilder;

final class SparqlSetRepository implements SetRepository
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
     * @return Set[]
     */
    public function all(int $offset = 0, int $limit = 100): array
    {
        $sparql = SparqlQueryBuilder::describeAllOfType(
            new Iri(Openskos::SET),
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
            $res[] = Set::fromTriples(new Iri($iriString), $group);
        }

        return $res;
    }

    public function find(Iri $iri): ?Set
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
            $res[] = Set::fromTriples(new Iri($iriString), $group);
        }

        return $res;
    }

    /**
     * @param Iri $rdfType
     * @param Iri $predicate
     * @param string $object
     * @return Set|null
     */
    public function findOneBy(Iri $rdfType, Iri $predicate, string $object): ?Set
    {
        $objects = $this->findBy($rdfType, $predicate, $object);
        if ($objects) {
            return $objects[0];
        }

        return null;
    }
}
