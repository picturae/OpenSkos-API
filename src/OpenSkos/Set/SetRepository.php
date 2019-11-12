<?php

declare(strict_types=1);

namespace App\OpenSkos\Set;

use App\Ontology\OpenSkos;
use App\Repository\AbstractRepository;

final class SetRepository extends AbstractRepository
{
    const DOCUMENT_CLASS = Set::class;
    const DOCUMENT_TYPE = OpenSkos::SET;
}
