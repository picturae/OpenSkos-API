<?php

declare(strict_types=1);

namespace App\OpenSkos\Label;

use App\OpenSkos\InternalResourceId;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Iri;

interface LabelRepository
{
    public function all(int $offset = 0, int $limit = 100, array $filter = []): array;

    public function findByIri(Iri $iri): ?AbstractRdfDocument;

    public function find(InternalResourceId $id): ?AbstractRdfDocument;

    public function findBy(Iri $predicate, InternalResourceId $object): ?array;

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?AbstractRdfDocument;

    public function findManyByIriList(array $iris): array;

    public function getOneWithoutUuid(InternalResourceId $subject): ?AbstractRdfDocument;
}
