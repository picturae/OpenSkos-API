<?php

declare(strict_types=1);

namespace App\OpenSkos\Set\Sparql;

use App\Ontology\OpenSkos;
use App\OpenSkos\Set\SetRepository;
use App\OpenSkos\Set\Set;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\Sparql\Client;
use App\Rdf\Iri;

final class SparqlSetRepository implements SetRepository
{
    /**
     * @var Client
     */
    private $rdfClient;

    /**
     * @var SkosResourceRepository<Set>
     */
    private $skosRepository;

    /**
     * @var OpenSkosIriFactory
     */
    private $iriFactory;

    public function __construct(
        Client $rdfClient,
        OpenSkosIriFactory $iriFactory
    ) {
        $this->rdfClient = $rdfClient;

        $this->skosRepository = new SkosResourceRepository(
            function (Iri $iri, array $triples): Set {
                return Set::fromTriples($iri, $triples);
            },
            $this->rdfClient
        );

        $this->iriFactory = $iriFactory;
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return Set[]
     */
    public function all(int $offset = 0, int $limit = 100): array
    {
        return $this->skosRepository->allOfType(
            new Iri(OpenSkos::SET),
            $offset,
            $limit
        );
    }

    public function findByIri(Iri $iri): ?Set
    {
        return $this->skosRepository->findByIri($iri);
    }

    /**
     * @param InternalResourceId $id
     *
     * @return Set|null
     */
    public function find(InternalResourceId $id): ?Set
    {
        return $this->findByIri($this->iriFactory->fromInternalResourceId($id));
    }

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return array
     */
    public function findBy(Iri $predicate, InternalResourceId $object): ?array
    {
        return $this->skosRepository->findBy(new Iri(OpenSkos::SET), $predicate, $object);
    }

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return Set|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Set
    {
        $res = $this->skosRepository->findOneBy(new Iri(OpenSkos::SET), $predicate, $object);

        return $res;
    }
}
