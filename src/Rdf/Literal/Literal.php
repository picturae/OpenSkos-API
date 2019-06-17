<?php

namespace App\Rdf\Literal;

use App\Rdf\Iri;
use App\Rdf\RdfTerm;

interface Literal extends RdfTerm
{
    public static function typeIri(): Iri;
}
