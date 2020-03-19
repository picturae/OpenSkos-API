<?php

declare(strict_types=1);

namespace App\OpenSkos\ConceptScheme;

use App\Ontology\Skos;
use App\Repository\AbstractRepository;

final class ConceptSchemeRepository extends AbstractRepository
{
    const DOCUMENT_CLASS = ConceptScheme::class;
    const DOCUMENT_TYPE  = Skos::CONCEPT_SCHEME;
}
