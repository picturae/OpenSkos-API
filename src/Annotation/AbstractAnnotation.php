<?php

namespace App\Annotation;

/**
 * @Annotation
 */
abstract class AbstractAnnotation
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $desc;
}
