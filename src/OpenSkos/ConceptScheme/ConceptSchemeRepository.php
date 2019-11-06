<?php

declare(strict_types=1);

namespace App\OpenSkos\ConceptScheme;

use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;

interface ConceptSchemeRepository
{
    public function all(int $offset = 0, int $limit = 100, array $filter = []): array;

    public function findByIri(Iri $iri): ?ConceptScheme;

    public function find(InternalResourceId $id): ?ConceptScheme;

    public function findBy(Iri $predicate, InternalResourceId $object): ?array;

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?ConceptScheme;
}
