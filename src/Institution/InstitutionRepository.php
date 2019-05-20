<?php

declare(strict_types=1);

namespace App\Institution;

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
    public function find(Iri $iri): ?Institution;
}
