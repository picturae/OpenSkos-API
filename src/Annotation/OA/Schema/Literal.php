<?php

namespace App\Annotation\OA\Schema;

use App\Annotation\AbstractAnnotation;

/**
 * @Annotation
 */
abstract class Literal extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var mixed
     */
    public $exmaple;

    /**
     * @var mixed
     */
    public $name;

    /**
     * @var mixed
     */
    public $example;

    /**
     * @var mixed
     */
    public $description;
}
