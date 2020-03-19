<?php

declare(strict_types=1);

namespace App\Repository;

use App\OpenSkos\InternalResourceId;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Iri;

interface RepositoryInterface
{
    public function all(int $offset = 0, int $limit = 100, array $filter = []): array;

    /**
     * @return AbstractRdfDocument|array|null
     */
    public function findByIri(Iri $iri);

    public function find(InternalResourceId $id): ?AbstractRdfDocument;

    public function findBy(Iri $predicate, InternalResourceId $object): ?array;

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?AbstractRdfDocument;
}
