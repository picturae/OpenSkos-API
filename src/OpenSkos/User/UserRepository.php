<?php

declare(strict_types=1);

namespace App\OpenSkos\User;

use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;

interface UserRepository
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
     * @return User|null
     */
    public function findByIri(Iri $iri): ?User;

    /**
     * @param InternalResourceId $id
     *
     * @return User|null
     */
    public function find(InternalResourceId $id): ?User;

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
     * @return User|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?User;
}
