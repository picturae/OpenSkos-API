<?php

namespace App\Annotation;

/**
 * @Annotation
 */
class Error extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $code = '';

    /**
     * @var array
     */
    public $fields = [];

    /**
     * @var string
     */
    public $description = '';
}
