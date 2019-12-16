<?php

namespace App\Annotation\OA;

use App\Annotation\AbstractAnnotation;

/**
 * @Annotation
 */
class Request extends AbstractAnnotation
{
    /**
     * @var array
     */
    public $parameters = [];
}
