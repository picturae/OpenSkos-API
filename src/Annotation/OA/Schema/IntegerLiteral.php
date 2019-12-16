<?php

namespace App\Annotation\OA\Schema;

/**
 * @Annotation
 */
class IntegerLiteral extends Literal
{
    public $type = 'integer';

    /**
     * @var string
     */
    public $format = 'int64';

    /**
     * @var int
     */
    public $example = 2019;
}
