<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept\Solr;

use App\Ontology\Skos;
use App\OpenSkos\Concept\Concept;
use App\OpenSkos\Concept\ConceptRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\Sparql\Client;
use App\Rdf\Iri;

final class SolrConceptRepository implements ConceptRepository
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
     *
     * @param Client             $rdfClient
     * @param OpenSkosIriFactory $iriFactory
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

    /**
     * @param int   $offset
     * @param int   $limit
     * @param array $filters
     *
     * @return array
     */
    public function all(int $offset = 0, int $limit = 100, array $filters = []): array
    {
        return $this->skosRepository->allOfType(
            new Iri(Skos::CONCEPT),
            $offset,
            $limit,
            $filters
        );
    }

    /**
     * @param Iri $iri
     *
     * @return Concept|null
     */
    public function findByIri(Iri $iri): ?Concept
    {
        return $this->skosRepository->findByIri($iri);
    }

    /**
     * @param InternalResourceId $id
     *
     * @return Concept|null
     */
    public function find(InternalResourceId $id): ?Concept
    {
        return $this->findByIri($this->iriFactory->fromInternalResourceId($id));
    }

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return array|null
     */
    public function findBy(Iri $predicate, InternalResourceId $object): ?array
    {
        return $this->skosRepository->findBy(new Iri(Skos::CONCEPT), $predicate, $object);
    }

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return Concept|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Concept
    {
        $res = $this->skosRepository->findOneBy(new Iri(Skos::CONCEPT), $predicate, $object);

        return $res;
    }
}
