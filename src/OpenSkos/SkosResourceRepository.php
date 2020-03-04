<?php

declare(strict_types=1);

namespace App\OpenSkos;

use App\Annotation\ErrorInherit;
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
     *
     * @ErrorInherit(class=Iri::class   , method="getUri"    )
     * @ErrorInherit(class=Triple::class, method="getSubject")
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
     *
     * @ErrorInherit(class=Iri::class                   , method="__construct"      )
     * @ErrorInherit(class=SkosResourceRepository::class, method="groupTriples"     )
     * @ErrorInherit(class=SparqlQuery::class           , method="describeAllOfType")
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
     * @param Iri $rdfType
     * @param Iri $iri
     * @return mixed
     * @psalm-return T|null
     *
     * @ErrorInherit(class=SparqlQuery::class, method="describeResource")
     */
    public function findByIri(Iri $rdfType, Iri $iri)
    {
        $sparql  = SparqlQuery::describeResourceOfType($rdfType, $iri);
        $triples = $this->rdfClient->describe($sparql);
        if (0 === count($triples)) {
            return null;
        }

        return call_user_func($this->resourceFactory, $iri, $triples);
    }

    /**
     * @ErrorInherit(class=Iri::class                   , method="__construct"     )
     * @ErrorInherit(class=SkosResourceRepository::class, method="groupTriples"    )
     * @ErrorInherit(class=SparqlQuery::class           , method="describeResource")
     */
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

    /**
     * @ErrorInherit(class=Iri::class                   , method="__construct"               )
     * @ErrorInherit(class=SkosResourceRepository::class, method="groupTriples"              )
     * @ErrorInherit(class=SparqlQuery::class           , method="describeByTypeAndPredicate")
     */
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
     *
     * @ErrorInherit(class=SkosResourceRepository::class, method="findBy")
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

    /**
     * @return mixed|null
     *
     * @ErrorInherit(class=Iri::class                   , method="__construct"            )
     * @ErrorInherit(class=SkosResourceRepository::class, method="findBy"                 )
     * @ErrorInherit(class=SkosResourceRepository::class, method="groupTriples"           )
     * @ErrorInherit(class=SparqlQuery::class           , method="describeSubjectFromUuid")
     * @ErrorInherit(class=SparqlQuery::class           , method="describeWithoutUuid"    )
     */
    public function getByUuid(InternalResourceId $subject)
    {
        // Indexed
        $sparql  = SparqlQuery::describeSubjectFromUuid((string) $subject);
        $triples = $this->rdfClient->describe($sparql);

        /*
         * Commented out. It:
         * 1) Caused ticket #47605
         * 2) A string filter on every triple in Jena is a performance hit
         * 3) No guarantee the objects described will be of the type being searched for.
         * 4) It first appeared in a commit for relation types, but uuids are not even mentioned in the specs for relations
         *
         * I've left the code here now, in case something is broken we can see the history
        */
//       // Fallback by filter
//       // TODO: add UUID field to all resources
//       if (!count($triples)) {
//           $sparql  = SparqlQuery::describeWithoutUuid((string) $subject);
//           $triples = $this->rdfClient->describe($sparql);
//       }

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
     * @ErrorInherit(class=Iri::class                   , method="__construct"              )
     * @ErrorInherit(class=SkosResourceRepository::class, method="groupTriples"             )
     * @ErrorInherit(class=SparqlQuery::class           , method="describeByTypeWithoutUuid")
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

    /**
     * @ErrorInherit(class=Iri::class        , method="getUri"       )
     * @ErrorInherit(class=SparqlQuery::class, method="deleteSubject")
     */
    public function deleteSubject(Iri $subject)
    {
        $sparql = SparqlQuery::deleteSubject($subject->getUri());

        return $this->rdfClient->delete($sparql);
    }
}
