<?php

declare(strict_types=1);

namespace App\OpenSkos;

use App\Rdf\Iri;
use App\Rdf\Sparql\Client;
use App\Rdf\Sparql\SparqlQuery;
use App\Rdf\Triple;

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
        $this->rdfClient       = $rdfClient;
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * Retreive the rdf client in use.
     */
    public function rdfClient(): Client
    {
        return $this->rdfClient;
    }

    /**
     * @param Triple[] $triples
     */
    public static function groupTriples(array $triples): array
    {
        $groups = [];
        foreach ($triples as $triple) {
            $groups[$triple->getSubject()->getUri()][] = $triple;
        }

        return $groups;
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

        $groups = $this::groupTriples($triples);

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
        $sparql  = SparqlQuery::describeResource($iri);
        $triples = $this->rdfClient->describe($sparql);
        if (0 === count($triples)) {
            return null;
        }

        return call_user_func($this->resourceFactory, $iri, $triples);
    }

    public function findManyByIriList(array $iris): array
    {
        $sparql  = SparqlQuery::describeResources($iris);
        $triples = $this->rdfClient->describe($sparql);
        if (0 === count($triples)) {
            return [];
        }

        $groups = $this::groupTriples($triples);

        $res = [];
        foreach ($groups as $iriString => $group) {
            $res[$iriString] = call_user_func($this->resourceFactory, new Iri($iriString), $group);
        }

        return $res;
    }

    public function findBy(Iri $rdfType, Iri $predicate, InternalResourceId $object): array
    {
        $sparql  = SparqlQuery::describeByTypeAndPredicate($rdfType, $predicate, $object);
        $triples = $this->rdfClient->describe($sparql);
        if (0 === count($triples)) {
            return [];
        }

        $groups = $this::groupTriples($triples);

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

    public function getByUuid(InternalResourceId $subject)
    {
        // Indexed
        $sparql  = SparqlQuery::describeSubjectFromUuid((string) $subject);
        $triples = $this->rdfClient->describe($sparql);

        // Fallback by filter
        // TODO: add UUID field to all resources
        if (!count($triples)) {
            $sparql  = SparqlQuery::describeWithoutUuid((string) $subject);
            $triples = $this->rdfClient->describe($sparql);
        }

        // None found = done
        if (!count($triples)) {
            return null;
        }

        // Multiple objects = broken
        $groups = static::groupTriples($triples);
        if (1 !== count($groups)) {
            return null;
        }

        // Turn triples into object
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
    public function getOneWithoutUuid(Iri $rdfType, InternalResourceId $subject)
    {
        $sparql  = SparqlQuery::describeByTypeWithoutUUID((string) $rdfType, (string) $subject);
        $triples = $this->rdfClient->describe($sparql);

        if (0 === count($triples)) {
            return null;
        }

        $groups = $this::groupTriples($triples);

        if (1 !== count($groups)) {
            return null;
        }

        foreach ($groups as $iriString => $group) {
            return call_user_func($this->resourceFactory, new Iri($iriString), $group);
        }

        return null;
    }

    /**
     * @param Triple[] $triples
     */
    public function insertTriples(array $triples): \EasyRdf_Http_Response
    {
        return $this->rdfClient->insertTriples($triples);
    }

    public function deleteSubject(Iri $subject)
    {
        $sparql = SparqlQuery::deleteSubject($subject->getUri());

        return $this->rdfClient->delete($sparql);
    }
}
