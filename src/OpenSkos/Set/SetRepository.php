<?php

declare(strict_types=1);

namespace App\OpenSkos\Set;

use App\Ontology\Org;
use App\Repository\AbstractRepository;

final class SetRepository extends AbstractRepository
{
    const DOCUMENT_CLASS = Set::class;
    const DOCUMENT_TYPE = Org::FORMAL_ORGANIZATION;
}
