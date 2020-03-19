<?php

namespace App\Annotation\OA;

use App\Annotation\AbstractAnnotation;

/**
 * @Annotation
 */
class Response extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $code = '200';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var mixed
     */
    public $content;
}
