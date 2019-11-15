<?php

declare(strict_types=1);

namespace App\Healthcheck;

use App\Ontology\Foaf;
use App\Repository\AbstractRepository;

final class JenaRepository extends AbstractRepository
{
    const DOCUMENT_CLASS = Person::class;
    const DOCUMENT_TYPE  = Foaf::PERSON;
}
