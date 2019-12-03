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

    /**
     * @var bool
     */
    public $required;

    /**
     * @var string
     */
    public $in;

    /**
     * @var mixed
     */
    public $enum;

    public function __toArray(): array
    {
        $data = parent::__toArray();
        if (!empty($data['enum'])) {
            $data['schema']['enum'] = $this->enum;
            unset($data['enum']);
        }

        return $data;
    }
}
