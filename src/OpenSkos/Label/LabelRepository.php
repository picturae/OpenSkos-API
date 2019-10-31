<?php

declare(strict_types=1);

namespace App\OpenSkos\Label;

use App\OpenSkos\InternalResourceId;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Iri;

interface LabelRepository
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
     * @return AbstractRdfDocument|null
     */
    public function findByIri(Iri $iri): ?AbstractRdfDocument;

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

    /**
     * @param array $iris
     *
     * @return array
     */
    public function findManyByIriList(array $iris): array;

    /**
     * @param InternalResourceId $subject
     *
     * @return AbstractRdfDocument|null
     */
    public function getOneWithoutUuid(InternalResourceId $subject): ?AbstractRdfDocument;
}
