<?php

declare(strict_types=1);

namespace App\OpenSkos\ConceptScheme\Sparql;

use App\Ontology\Skos;
use App\OpenSkos\ConceptScheme\ConceptScheme;
use App\OpenSkos\ConceptScheme\ConceptSchemeRepository;
use App\Ontology\Org;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\Sparql\Client;
use App\Rdf\Iri;

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
            function (Iri $iri, array $triples): ConceptScheme {
                return ConceptScheme::fromTriples($iri, $triples);
            },
            $this->rdfClient
        );

        $this->iriFactory = $iriFactory;
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function all(int $offset = 0, int $limit = 100): array
    {
        return $this->skosRepository->allOfType(
            new Iri(Skos::CONCEPTSCHEME),
            $offset,
            $limit
        );
    }

    /**
     * @param Iri $iri
     *
     * @return ConceptScheme|null
     */
    public function findByIri(Iri $iri): ?ConceptScheme
    {
        return $this->skosRepository->findByIri($iri);
    }

    /**
     * @param InternalResourceId $id
     *
     * @return ConceptScheme|null
     */
    public function find(InternalResourceId $id): ?ConceptScheme
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
        return $this->skosRepository->findBy(new Iri(Org::FORMALORG), $predicate, $object);
    }

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return ConceptScheme|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?ConceptScheme
    {
        $res = $this->skosRepository->findOneBy(new Iri(Org::FORMALORG), $predicate, $object);

        return $res;
    }
}
