<?php

declare(strict_types=1);

namespace App\OpenSkos\Set\Sparql;

use App\Ontology\OpenSkos;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\Set\Set;
use App\OpenSkos\Set\SetRepository;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\Iri;
use App\Rdf\Sparql\Client;

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
     * @return Set[]
     */
    public function all(int $offset = 0, int $limit = 100, array $filters = []): array
    {
        return $this->skosRepository->allOfType(
            new Iri(OpenSkos::SET),
            $offset,
            $limit,
            $filters
        );
    }

    public function findByIri(Iri $iri): ?Set
    {
        return $this->skosRepository->findByIri($iri);
    }

    public function find(InternalResourceId $id): ?Set
    {
        return $this->findByIri($this->iriFactory->fromInternalResourceId($id));
    }

    /**
     * @return array
     */
    public function findBy(Iri $predicate, InternalResourceId $object): ?array
    {
        return $this->skosRepository->findBy(new Iri(OpenSkos::SET), $predicate, $object);
    }

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Set
    {
        $res = $this->skosRepository->findOneBy(new Iri(OpenSkos::SET), $predicate, $object);

        return $res;
    }
}
