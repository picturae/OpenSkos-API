<?php

declare(strict_types=1);

namespace App\OpenSkos\Set;

use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;

interface SetRepository
{
    /**
     * @return Set[]
     */
    public function all(int $offset = 0, int $limit = 100, array $filters = []): array;

    public function find(InternalResourceId $id): ?Set;

    /**
     * @return array
     */
    public function findBy(Iri $predicate, InternalResourceId $object): ?array;

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Set;
}
