<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept;

use App\Ontology\Skos;
use App\Repository\AbstractSolrRepository;

class ConceptRepository extends AbstractSolrRepository
{
    const DOCUMENT_CLASS = Concept::class;
    const DOCUMENT_TYPE  = Skos::CONCEPT;
}
