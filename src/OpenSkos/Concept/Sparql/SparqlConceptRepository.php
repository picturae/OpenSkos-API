<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept\Sparql;

use App\Ontology\Skos;
use App\OpenSkos\Concept\Concept;
use App\OpenSkos\Concept\ConceptRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\Iri;
use App\Rdf\Sparql\Client;

final class SparqlConceptRepository implements ConceptRepository
{
    /**
     * @var Client
     */
    private $rdfClient;

    /**
     * @var SkosResourceRepository<Concept>
     */
    private $skosRepository;

    /**
     * @var OpenSkosIriFactory
     */
    private $iriFactory;

    /**
     * SparqlConceptRepository constructor.
     */
    public function __construct(
        Client $rdfClient,
        OpenSkosIriFactory $iriFactory
    ) {
        $this->rdfClient = $rdfClient;

        $this->skosRepository = new SkosResourceRepository(
            function (Iri $iri, array $triples): Concept {
                return Concept::fromTriples($iri, $triples);
            },
            $this->rdfClient
        );

        $this->iriFactory = $iriFactory;
    }

    public function all(int $offset = 0, int $limit = 100, array $filters = []): array
    {
        return $this->skosRepository->allOfType(
            new Iri(Skos::CONCEPT),
            $offset,
            $limit,
            $filters
        );
    }

    public function findByIri(Iri $iri): ?Concept
    {
        return $this->skosRepository->findByIri($iri);
    }

    public function find(InternalResourceId $id): ?Concept
    {
        return $this->findByIri($this->iriFactory->fromInternalResourceId($id));
    }

    public function findBy(Iri $predicate, InternalResourceId $object): ?array
    {
        return $this->skosRepository->findBy(new Iri(Skos::CONCEPT), $predicate, $object);
    }

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Concept
    {
        $res = $this->skosRepository->findOneBy(new Iri(Skos::CONCEPT), $predicate, $object);

        return $res;
    }
}
