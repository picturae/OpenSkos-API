<?php

declare(strict_types=1);

namespace App\Rdf\Sparql;

use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;
use App\Rdf\RdfResource;

interface SparqlRepositoryInterface
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
     * @return RdfResource|null
     */
    public function findByIri(Iri $iri): ?RdfResource;

    /**
     * @param InternalResourceId $id
     *
     * @return RdfResource|null
     */
    public function find(InternalResourceId $id): ?RdfResource;

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
     * @return RdfResource|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?RdfResource;

    /**
     * @param array $iris
     *
     * @return array
     */
    public function findManyByIriList(array $iris): array;

    /**
     * @param InternalResourceId $subject
     *
     * @return RdfResource|null
     */
    public function getOneWithoutUuid(InternalResourceId $subject): ?RdfResource;
}
