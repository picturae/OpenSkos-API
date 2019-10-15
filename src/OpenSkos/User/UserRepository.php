<?php

declare(strict_types=1);

namespace App\OpenSkos\User;

use App\Ontology\Org;
use App\Repository\AbstractRepository;

final class UserRepository extends AbstractRepository
{
    const DOCUMENT_CLASS = User::class;
    const DOCUMENT_TYPE = Org::FORMALORG;
}
