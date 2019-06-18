<?php

declare(strict_types=1);

namespace App\Rdf\Exception;

use App\Rdf\Iri;

final class UnknownProperty extends \InvalidArgumentException
{
    public function __construct(Iri $property)
    {
        parent::__construct("Property '$property' is not expected");
    }
}
