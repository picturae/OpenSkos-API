<?php

declare(strict_types=1);

namespace App\OpenSkos\User\Sparql;

use App\Ontology\Org;
use App\OpenSkos\User\User;
use App\Repository\AbstractRepository;

final class SparqlUserRepository extends AbstractRepository
{
    const DOCUMENT_CLASS = User::class;
    const DOCUMENT_TYPE = Org::FORMALORG;
}
