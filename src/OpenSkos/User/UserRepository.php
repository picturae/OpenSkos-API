<?php

declare(strict_types=1);

namespace App\OpenSkos\User;

use App\Ontology\Foaf;
use App\Repository\AbstractRepository;

final class UserRepository extends AbstractRepository
{
    const DOCUMENT_CLASS = User::class;
    const DOCUMENT_TYPE  = Foaf::PERSON;
}
