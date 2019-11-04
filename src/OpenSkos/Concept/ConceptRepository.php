<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept;

use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;

interface ConceptRepository
{
    public function all(int $offset = 0, int $limit = 100, array $filter = []): array;

    public function findByIri(Iri $iri): ?Concept;

    public function find(InternalResourceId $id): ?Concept;

    public function findBy(Iri $predicate, InternalResourceId $object): ?array;

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Concept;
}
