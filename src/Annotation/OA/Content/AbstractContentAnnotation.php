<?php

namespace App\Annotation\OA\Content;

use App\Annotation\OA\Schema\ObjectLiteral;

abstract class AbstractContentAnnotation extends ObjectLiteral
{
    /**
     * @var string
     */
    public $mimetype;

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
