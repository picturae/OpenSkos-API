<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution\Sparql;

use App\OpenSkos\Institution\Institution;
use App\OpenSkos\Institution\InstitutionRepository;
use App\Ontology\Org;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\Sparql\Client;
use App\Rdf\Iri;

final class SparqlInstitutionRepository implements InstitutionRepository
{
    /**
     * @var Client
     */
    private $rdfClient;

    /**
     * @var SkosResourceRepository<Institution>
     */
    private $skosRepository;

    /**
     * @var OpenSkosIriFactory
     */
    private $iriFactory;

    /**
     * SparqlInstitutionRepository constructor.
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
            function (Iri $iri, array $triples): Institution {
                return Institution::fromTriples($iri, $triples);
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
            new Iri(Org::FORMAL_ORGANIZATION),
            $offset,
            $limit
        );
    }

    /**
     * @param Iri $iri
     *
     * @return Institution|null
     */
    public function findByIri(Iri $iri): ?Institution
    {
        return $this->skosRepository->findByIri($iri);
    }

    /**
     * @param InternalResourceId $id
     *
     * @return Institution|null
     */
    public function find(InternalResourceId $id): ?Institution
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
        return $this->skosRepository->findBy(new Iri(Org::FORMAL_ORGANIZATION), $predicate, $object);
    }

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return Institution|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Institution
    {
        $res = $this->skosRepository->findOneBy(new Iri(Org::FORMAL_ORGANIZATION), $predicate, $object);

        return $res;
    }
}
