<?php

declare(strict_types=1);

namespace App\Repository;

use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Sparql\Client;
use App\Rdf\Iri;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var string
     */
    const DOCUMENT_CLASS = null;

    /**
     * @var string
     */
    const DOCUMENT_TYPE = null;

    /**
     * @var Client
     */
    protected $rdfClient;

    /**
     * @var SkosResourceRepository<AbstractRdfDocument>
     */
    protected $skosRepository;

    /**
     * @var OpenSkosIriFactory
     */
    protected $iriFactory;

    /**
     * AbstractRepository constructor.
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
            function (Iri $iri, array $triples): AbstractRdfDocument {
                return call_user_func(static::DOCUMENT_CLASS.'::fromTriples', $iri, $triples);
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
            new Iri(static::DOCUMENT_TYPE),
            $offset,
            $limit,
            $filters
        );
    }

    /**
     * @param Iri $iri
     *
     * @return AbstractRdfDocument|null
     */
    public function findByIri(Iri $iri): ?AbstractRdfDocument
    {
        return $this->skosRepository->findByIri($iri);
    }

    /**
     * @param InternalResourceId $id
     *
     * @return AbstractRdfDocument|null
     */
    public function find(InternalResourceId $id): ?AbstractRdfDocument
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
        return $this->skosRepository->findBy(new Iri(static::DOCUMENT_TYPE), $predicate, $object);
    }

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return AbstractRdfDocument|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?AbstractRdfDocument
    {
        $res = $this->skosRepository->findOneBy(new Iri(static::DOCUMENT_TYPE), $predicate, $object);

        return $res;
    }
}
