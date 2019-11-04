<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution\Sparql;

use App\Ontology\Org;
use App\OpenSkos\Institution\Institution;
use App\OpenSkos\Institution\InstitutionRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\Iri;
use App\Rdf\Sparql\Client;

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

    public function all(int $offset = 0, int $limit = 100): array
    {
        return $this->skosRepository->allOfType(
            new Iri(Org::FORMAL_ORGANIZATION),
            $offset,
            $limit
        );
    }

    public function findByIri(Iri $iri): ?Institution
    {
        return $this->skosRepository->findByIri($iri);
    }

    public function find(InternalResourceId $id): ?Institution
    {
        return $this->findByIri($this->iriFactory->fromInternalResourceId($id));
    }

    public function findBy(Iri $predicate, InternalResourceId $object): ?array
    {
        return $this->skosRepository->findBy(new Iri(Org::FORMAL_ORGANIZATION), $predicate, $object);
    }

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Institution
    {
        $res = $this->skosRepository->findOneBy(new Iri(Org::FORMAL_ORGANIZATION), $predicate, $object);

        return $res;
    }
}
