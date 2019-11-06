<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution;

use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;

interface InstitutionRepository
{
    public function all(int $offset = 0, int $limit = 100): array;

    public function findByIri(Iri $iri): ?Institution;

    public function find(InternalResourceId $id): ?Institution;

    public function findBy(Iri $predicate, InternalResourceId $object): ?array;

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Institution;
}
