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
     * TODO: add returntype ?Label once PHP7.4 is stable
     *      in 7.0 through 7.3, there's a bug where extends aren't honoured by return types.
     *
     * @param Iri $iri
     *
     * @return Label|null
     */
    public function findByIri(Iri $iri);

    /**
     * TODO: add returntype ?Label once PHP7.4 is stable
     *      in 7.0 through 7.3, there's a bug where extends aren't honoured by return types.
     *
     * @param InternalResourceId $id
     *
     * @return Label|null
     */
    public function find(InternalResourceId $id);

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return array|null
     */
    public function findBy(Iri $predicate, InternalResourceId $object): ?array;

    /**
     * TODO: add returntype ?Label once PHP7.4 is stable
     *      in 7.0 through 7.3, there's a bug where extends aren't honoured by return types.
     *
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return Label|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object);

    /**
     * @param array $iris
     *
     * @return array
     */
    public function findManyByIriList(array $iris): array;

    /**
     * TODO: add returntype ?Label once PHP7.4 is stable
     *      in 7.0 through 7.3, there's a bug where extends aren't honoured by return types.
     *
     * @param InternalResourceId $subject
     *
     * @return Label|null
     */
    public function getOneWithoutUuid(InternalResourceId $subject);
}
