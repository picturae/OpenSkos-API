<?php

declare(strict_types=1);

namespace App\OpenSkos\ConceptScheme;

use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;

interface ConceptSchemeRepository
{
    /**
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function all(int $offset = 0, int $limit = 100): array;

    /**
     * @param Iri $iri
     *
     * @return ConceptScheme|null
     */
    public function findByIri(Iri $iri): ?ConceptScheme;

    /**
     * @param InternalResourceId $id
     *
     * @return ConceptScheme|null
     */
    public function find(InternalResourceId $id): ?ConceptScheme;

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return array|null
     */
    public function findBy(Iri $predicate, InternalResourceId $object): ?array;

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return ConceptScheme|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?ConceptScheme;
}
