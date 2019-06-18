<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution;

use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;

interface InstitutionRepository
{
    /**
     * @param int $limit
     * @param int $offset
     *
     * @return Institution[]
     */
    public function all(int $offset = 0, int $limit = 100): array;

    /**
     * @param Iri $iri
     *
     * @return Institution|null
     */
    public function findByIri(Iri $iri): ?Institution;

    /**
     * @param InternalResourceId $id
     *
     * @return Institution|null
     */
    public function find(InternalResourceId $id): ?Institution;
}
