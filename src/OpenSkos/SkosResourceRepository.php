<?php

declare(strict_types=1);

namespace App\OpenSkos;

use App\Rdf\Iri;
use App\Rdf\Sparql\Client;
use App\Rdf\Sparql\SparqlQuery;

/**
 * @template T
 */
final class SkosResourceRepository
{
    /**
     * @var Client
     */
    private $rdfClient;

    /**
     * @var callable
     */
    private $resourceFactory;

    /**
     * SkosResourceRepository constructor.
     *
     * @param callable(Iri, array<\App\Rdf\Triple>): T $resourceFactory
     * @param Client                                   $rdfClient
     */
    public function __construct(
        callable $resourceFactory,
        Client $rdfClient
    ) {
        $this->rdfClient = $rdfClient;
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @param Iri $type
     * @param int $offset
     * @param int $limit
     *
     * @return array
     * @psalm-return array<T>
     */
    public function allOfType(
        Iri $type,
        int $offset = 0,
        int $limit = 100
    ): array {
        $sparql = SparqlQuery::describeAllOfType(
            $type,
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
            $res[] = call_user_func($this->resourceFactory, new Iri($iriString), $group);
        }

        return $res;
    }

    /**
     * @param Iri $iri
     *
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

    /**
     * @param Iri                $rdfType
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return array
     */
    public function findBy(Iri $rdfType, Iri $predicate, InternalResourceId $object): ?array
    {
        $sparql = SparqlQuery::describeByTypeAndPredicate($rdfType, $predicate, $object);
        $triples = $this->rdfClient->describe($sparql);
        if (0 === count($triples)) {
            return null;
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
     * @param Iri                $rdfType
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return array|mixed|null
     */
    public function findOneBy(Iri $rdfType, Iri $predicate, InternalResourceId $object)
    {
        $fullSet = $this->findBy($rdfType, $predicate, $object);
        if (isset($fullSet) && is_array($fullSet)) {
            $resourceTriples = $fullSet[0];

            return $resourceTriples;
        }

        return $fullSet;
    }
}
