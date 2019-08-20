<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept;

use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;

interface ConceptRepository
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
     * @return Concept|null
     */
    public function findByIri(Iri $iri): ?Concept;

    /**
     * @param InternalResourceId $id
     *
     * @return Concept|null
     */
    public function find(InternalResourceId $id): ?Concept;

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
     * @return Concept|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Concept;
}
