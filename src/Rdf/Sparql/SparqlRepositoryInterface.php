<?php

declare(strict_types=1);

namespace App\Rdf\Sparql;

use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;
use App\Rdf\RdfResource;

interface SparqlRepositoryInterface
{
    public function all(int $offset = 0, int $limit = 100, array $filter = []): array;

    public function findByIri(Iri $iri): ?RdfResource;

    public function find(InternalResourceId $id): ?RdfResource;

    public function findBy(Iri $predicate, InternalResourceId $object): ?array;

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?RdfResource;

    public function findManyByIriList(array $iris): array;

    public function getOneWithoutUuid(InternalResourceId $subject): ?RdfResource;
}
