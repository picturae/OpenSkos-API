<?php

declare(strict_types=1);

namespace App\OpenSkos;

use App\Rdf\Iri;
use App\Rdf\Sparql\Client;
use App\Rdf\Sparql\SparqlQuery;

/**
 * @template T
 */
class SkosResourceRepository
{
    /**
     * @var Client
     */
    protected $rdfClient;

    /**
     * @var callable
     */
    protected $resourceFactory;

    /**
     * SkosResourceRepository constructor.
     *
     * @param callable(Iri, array<\App\Rdf\Triple>): T $resourceFactory
     */
    public function __construct(
        callable $resourceFactory,
        Client $rdfClient
    ) {
        $this->rdfClient = $rdfClient;
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @psalm-return array<T>
     */
    public function allOfType(
        Iri $type,
        int $offset = 0,
        int $limit = 100,
        array $filters = []
    ): array {
        $sparql = SparqlQuery::describeAllOfType(
            $type,
            $offset,
            $limit,
            $filters
        );
        $triples = $this->rdfClient->describe($sparql);

        //TODO: Move to separate helper class?
        $groups = [];
        foreach ($triples as $triple) {
            $groups[$triple->getSubject()->getUri()][] = $triple;
        }

        $res = [];
        foreach ($groups as $iriString => $group) {
            $res[] = call_user_func($this->resourceFactory, new Iri($iriString), $group);
        }

        return $res;
    }

    /**
     * @return mixed
     * @psalm-return T|null
     */
    public function findByIri(Iri $iri)
    {
        $sparql = SparqlQuery::describeResource($iri);
        $triples = $this->rdfClient->describe($sparql);
        if (0 === count($triples)) {
            return null;
        }

        return call_user_func($this->resourceFactory, $iri, $triples);
    }

    public function findManyByIriList(array $iris): array
    {
        $sparql = SparqlQuery::describeResources($iris);
        $triples = $this->rdfClient->describe($sparql);
        if (0 === count($triples)) {
            return [];
        }

        //TODO: Move to separate helper class?
        $groups = [];
        foreach ($triples as $triple) {
            $groups[$triple->getSubject()->getUri()][] = $triple;
        }

        $res = [];
        foreach ($groups as $iriString => $group) {
            $res[$iriString] = call_user_func($this->resourceFactory, new Iri($iriString), $group);
        }

        return $res;
    }

    public function findBy(Iri $rdfType, Iri $predicate, InternalResourceId $object): array
    {
        $sparql = SparqlQuery::describeByTypeAndPredicate($rdfType, $predicate, $object);
        $triples = $this->rdfClient->describe($sparql);
        if (0 === count($triples)) {
            return [];
        }

        //TODO: Move to separate helper class?
        $groups = [];
        foreach ($triples as $triple) {
            $groups[$triple->getSubject()->getUri()][] = $triple;
        }

        $res = [];
        foreach ($groups as $iriString => $group) {
            $res[] = call_user_func($this->resourceFactory, new Iri($iriString), $group);
        }

        return $res;
    }

    /**
     * @return array|mixed|null
     */
    public function findOneBy(Iri $rdfType, Iri $predicate, InternalResourceId $object)
    {
        $fullSet = $this->findBy($rdfType, $predicate, $object);
        if (isset($fullSet) && is_array($fullSet)) {
            if (count($fullSet)) {
                $resourceTriples = $fullSet[0];

                return $resourceTriples;
            } else {
                return null;
            }
        }

        return $fullSet;
    }

    public function getOneWithoutUuid(Iri $rdfType, InternalResourceId $subject)
    {
        $sparql = SparqlQuery::describeByTypeWithoutUUID((string) $rdfType, (string) $subject);
        $triples = $this->rdfClient->describe($sparql);

        if (0 === count($triples)) {
            return null;
        }

        $groups = [];
        foreach ($triples as $triple) {
            $groups[$triple->getSubject()->getUri()][] = $triple;
        }

        if (1 !== count($groups)) {
            return null;
        }

        foreach ($groups as $iriString => $group) {
            return call_user_func($this->resourceFactory, new Iri($iriString), $group);
        }

        return null;
    }

    /**
     * Fetches a resource directly by it's subject.
     *
     * @return mixed|null
     */
    public function get(Iri $subject)
    {
        $sparql = SparqlQuery::describeResource($subject);
        $triples = $this->rdfClient->describe($sparql);

        if (0 === count($triples)) {
            return null;
        }

        $groups = [];
        foreach ($triples as $triple) {
            $groups[$triple->getSubject()->getUri()][] = $triple;
        }

        if (1 !== count($groups)) {
            return null;
        }

        foreach ($groups as $iriString => $group) {
            return call_user_func($this->resourceFactory, new Iri($iriString), $group);
        }

        return null;
    }
}
