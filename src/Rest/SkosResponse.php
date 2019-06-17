<?php

namespace App\Rest;

use App\Rdf\Format\RdfFormat;

interface SkosResponse
{
    public function format(): RdfFormat;
}
