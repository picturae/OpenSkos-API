<?php

declare(strict_types=1);

namespace App\OpenSkos\Set;

use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;

interface SetRepository
{
    /**
     * @param int $limit
     * @param int $offset
     *
     * @return Set[]
     */
    public function all(int $offset = 0, int $limit = 100): array;

    /**
     * @param InternalResourceId $id
     *
     * @return Set|null
     */
    public function find(InternalResourceId $id): ?Set;

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return array
     */
    public function findBy(Iri $predicate, InternalResourceId $object): ?array;

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return Set|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Set;
}
