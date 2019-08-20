<?php

declare(strict_types=1);

namespace App\OpenSkos\Label;

use App\OpenSkos\InternalResourceId;
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
     * @return Label|null
     */
    public function findByIri(Iri $iri): ?Label;

    /**
     * @param InternalResourceId $id
     *
     * @return Label|null
     */
    public function find(InternalResourceId $id): ?Label;

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
     * @return Label|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Label;
}
