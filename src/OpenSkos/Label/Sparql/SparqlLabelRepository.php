<?php

declare(strict_types=1);

namespace App\OpenSkos\Label\Sparql;

use App\Ontology\SkosXl;
use App\OpenSkos\Label\Label;
use App\OpenSkos\Label\LabelRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\Sparql\Client;
use App\Rdf\Iri;

final class SparqlLabelRepository implements LabelRepository
{
    /**
     * @var Client
     */
    private $rdfClient;

    /**
     * @var SkosResourceRepository<Label>
     */
    private $skosRepository;

    /**
     * @var OpenSkosIriFactory
     */
    private $iriFactory;

    /**
     * SparqlLabelRepository constructor.
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
            function (Iri $iri, array $triples): Label {
                return Label::fromTriples($iri, $triples);
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
            new Iri(SkosXl::LABEL),
            $offset,
            $limit,
            $filters
        );
    }

    /**
     * @param Iri $iri
     *
     * @return Label|null
     */
    public function findByIri(Iri $iri): ?Label
    {
        return $this->skosRepository->findByIri($iri);
    }

    /**
     * @param array $iris
     *
     * @return array
     */
    public function findManyByIriList(array $iris): array
    {
        return $this->skosRepository->findManyByIriList($iris);
    }

    /**
     * @param InternalResourceId $id
     *
     * @return Label|null
     */
    public function find(InternalResourceId $id): ?Label
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
        return $this->skosRepository->findBy(new Iri(SkosXl::LABEL), $predicate, $object);
    }

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return Label|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Label
    {
        $res = $this->skosRepository->findOneBy(new Iri(SkosXl::LABEL), $predicate, $object);

        return $res;
    }
}
