<?php

declare(strict_types=1);

namespace App\Rdf;

interface ResourceRepository
{
    /**
     * @param Iri $iriType
     * @param int $offset
     * @param int $limit
     *
     * @return RdfResource[]
     */
    public function allOfType(
        Iri $iriType,
        int $offset = 0,
        int $limit = 100
    ): array;

    public function findByIri(
        Iri $iri
    ): ?RdfResource;
}
