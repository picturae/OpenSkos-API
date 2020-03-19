<?php

namespace App\Annotation\OA\Schema;

/**
 * @Annotation
 */
class ArrayLiteral extends Literal
{
    public $type = 'array';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var mixed
     */
    public $items;
}
