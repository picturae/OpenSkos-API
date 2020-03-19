<?php

declare(strict_types=1);

namespace App\OpenSkos\Label;

use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;
use App\Rdf\RdfResource;

interface LabelRepository
{
    public function all(int $offset = 0, int $limit = 100, array $filter = []): array;

    /**
     * TODO: add returntype ?Label once PHP7.4 is stable
     *      in 7.0 through 7.3, there's a bug where extends aren't honoured by return types.
     *
     * @return RdfResource|null
     */
    public function findByIri(Iri $iri);

    /**
     * TODO: add returntype ?Label once PHP7.4 is stable
     *      in 7.0 through 7.3, there's a bug where extends aren't honoured by return types.
     *
     * @return RdfResource|null
     */
    public function find(InternalResourceId $id);

    public function findBy(Iri $predicate, InternalResourceId $object): ?array;

    /**
     * TODO: add returntype ?Label once PHP7.4 is stable
     *      in 7.0 through 7.3, there's a bug where extends aren't honoured by return types.
     *
     * @return RdfResource|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object);

    public function findManyByIriList(array $iris): array;

    /**
     * TODO: add returntype ?Label once PHP7.4 is stable
     *      in 7.0 through 7.3, there's a bug where extends aren't honoured by return types.
     *
     * @return RdfResource|null
     */
    public function getOneWithoutUuid(InternalResourceId $subject);
}
