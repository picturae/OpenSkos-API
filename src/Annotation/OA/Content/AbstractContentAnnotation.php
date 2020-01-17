<?php

namespace App\Annotation\OA\Content;

use App\Annotation\OA\Schema\ObjectLiteral;

abstract class AbstractContentAnnotation extends ObjectLiteral
{
    /**
     * @var string
     */
    public $mimetype;

    /**
     * @return AbstractContentAnnotation
     */
    public static function instance()
    {
        $classname = get_called_class();

        return new $classname();
    }

    public function __toArray(): array
    {
        $mime = $this->mimetype;

        return [
            $mime => [
                'schema' => parent::__toArray(),
            ],
        ];
    }
}
