<?php

declare(strict_types=1);

namespace App\OpenSkos\Label\Sparql;

use App\Ontology\SkosXl;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Label\Label;
use App\OpenSkos\Label\LabelRepository;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Iri;
use App\Rdf\Sparql\Client;

final class SparqlLabelRepository implements LabelRepository
{
    /**
     * @var Client
     */
    private $rdfClient;

    /**
     * @var SkosResourceRepository<AbstractRdfDocument>
     */
    private $skosRepository;

    /**
     * @var OpenSkosIriFactory
     */
    private $iriFactory;

    /**
     * SparqlLabelRepository constructor.
     */
    public function __construct(
        Client $rdfClient,
        OpenSkosIriFactory $iriFactory
    ) {
        $this->rdfClient = $rdfClient;

        $this->skosRepository = new SkosResourceRepository(
            function (Iri $iri, array $triples): AbstractRdfDocument {
                return Label::fromTriples($iri, $triples);
            },
            $this->rdfClient
        );

        $this->iriFactory = $iriFactory;
    }

    public function all(int $offset = 0, int $limit = 100, array $filters = []): array
    {
        return $this->skosRepository->allOfType(
            new Iri(SkosXl::LABEL),
            $offset,
            $limit,
            $filters
        );
    }

    public function findByIri(Iri $iri): ?AbstractRdfDocument
    {
        return $this->skosRepository->findByIri($iri);
    }

    public function findManyByIriList(array $iris): array
    {
        return $this->skosRepository->findManyByIriList($iris);
    }

    public function find(InternalResourceId $id): ?AbstractRdfDocument
    {
        return $this->findByIri($this->iriFactory->fromInternalResourceId($id));
    }

    public function findBy(Iri $predicate, InternalResourceId $object): ?array
    {
        return $this->skosRepository->findBy(new Iri(SkosXl::LABEL), $predicate, $object);
    }

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?AbstractRdfDocument
    {
        $res = $this->skosRepository->findOneBy(new Iri(SkosXl::LABEL), $predicate, $object);

        return $res;
    }

    public function getOneWithoutUuid(InternalResourceId $uuid): ?AbstractRdfDocument
    {
        $res = $this->skosRepository->getOneWithoutUuid(new Iri(SkosXl::LABEL), $uuid);

        return $res;
    }
}
