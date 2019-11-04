<?php

declare(strict_types=1);

namespace App\OpenSkos\ConceptScheme\Sparql;

use App\Ontology\Skos;
use App\OpenSkos\ConceptScheme\ConceptScheme;
use App\OpenSkos\ConceptScheme\ConceptSchemeRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\Iri;
use App\Rdf\Sparql\Client;

final class SparqlConceptSchemeRepository implements ConceptSchemeRepository
{
    /**
     * @var Client
     */
    private $rdfClient;

    /**
     * @var SkosResourceRepository<ConceptScheme>
     */
    private $skosRepository;

    /**
     * @var OpenSkosIriFactory
     */
    private $iriFactory;

    /**
     * SparqlConceptSchemeRepository constructor.
     */
    public function __construct(
        Client $rdfClient,
        OpenSkosIriFactory $iriFactory
    ) {
        $this->rdfClient = $rdfClient;

        $this->skosRepository = new SkosResourceRepository(
            function (Iri $iri, array $triples): ConceptScheme {
                return ConceptScheme::fromTriples($iri, $triples);
            },
            $this->rdfClient
        );

        $this->iriFactory = $iriFactory;
    }

    public function all(int $offset = 0, int $limit = 100, array $filters = []): array
    {
        return $this->skosRepository->allOfType(
            new Iri(Skos::CONCEPT_SCHEME),
            $offset,
            $limit,
            $filters
        );
    }

    public function findByIri(Iri $iri): ?ConceptScheme
    {
        return $this->skosRepository->findByIri($iri);
    }

    public function find(InternalResourceId $id): ?ConceptScheme
    {
        return $this->findByIri($this->iriFactory->fromInternalResourceId($id));
    }

    public function findBy(Iri $predicate, InternalResourceId $object): ?array
    {
        return $this->skosRepository->findBy(new Iri(Skos::CONCEPT_SCHEME), $predicate, $object);
    }

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?ConceptScheme
    {
        $res = $this->skosRepository->findOneBy(new Iri(Skos::CONCEPT_SCHEME), $predicate, $object);

        return $res;
    }
}
