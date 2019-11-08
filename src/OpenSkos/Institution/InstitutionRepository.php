<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution;

use App\Ontology\Org;
use App\Repository\AbstractRepository;

final class InstitutionRepository extends AbstractRepository
{
    const DOCUMENT_CLASS = Institution::class;
    const DOCUMENT_TYPE = Org::FORMAL_ORGANIZATION;
}
