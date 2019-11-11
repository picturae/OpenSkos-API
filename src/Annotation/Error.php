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

    /**
     * @var int
     */
    public $status = 500;

    /**
     * @var string
     */
    public $realm = '';
}
