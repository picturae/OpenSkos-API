<?php

declare(strict_types=1);

namespace App\Repository;

use App\OpenSkos\InternalResourceId;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Iri;

interface RepositoryInterface
{
    /**
     * @param int   $offset
     * @param int   $limit
     * @param array $filter
     *
     * @return array
     */
    public function all(int $offset = 0, int $limit = 100, array $filter = []): array;

    /**
     * @param Iri $iri
     *
     * @return AbstractRdfDocument|array|null
     */
    public function findByIri(Iri $iri);

    /**
     * @param InternalResourceId $id
     *
     * @return AbstractRdfDocument|null
     */
    public function find(InternalResourceId $id): ?AbstractRdfDocument;

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return array|null
     */
    public function findBy(Iri $predicate, InternalResourceId $object): ?array;

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return AbstractRdfDocument|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?AbstractRdfDocument;
}
