<?php

declare(strict_types=1);

namespace App\Set;

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
     * @param Iri $iri
     *
     * @return Set|null
     */
    public function find(Iri $iri): ?Set;

    /**
     * @param Iri $iri
     *
     * @return array
     */
    public function findBy(Iri $rdfType, Iri $predicate, string $object): array;


    /**
     * @param Iri $iri
     *
     * @return Set|null
     */
    public function findOneBy(Iri $rdfType, Iri $predicate, string $object): ?Set;
}
