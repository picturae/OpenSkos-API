<?php

namespace App\Annotation\OA\Content;

/**
 * @Annotation
 */
class Rdf extends AbstractMultiType
{
    public $typeClasses = [
        JsonRdf::class,
        Ntriples::class,
        Turtle::class,
    ];
}
