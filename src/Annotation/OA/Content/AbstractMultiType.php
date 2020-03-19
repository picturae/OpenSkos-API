<?php

namespace App\Annotation\OA\Content;

abstract class AbstractMultiType extends AbstractContentAnnotation
{
    /**
     * @var array
     */
    public $typeClasses = [];

    public $mimetype = 'n/a';

    public function __toArray(): array
    {
        $output = [];

        foreach ($this->typeClasses as $classname) {
            $this->mimetype = $classname::instance()->mimetype;
            $output         = array_merge($output, parent::__toArray());
        }

        $this->mimetype = 'n/a';

        return $output;
    }
}
