<?php

namespace App\Annotation\OA\Content;

/**
 * @Annotation
 */
class Json extends AbstractContentAnnotation
{
    /**
     * @var string
     */
    public $mimetype = 'application/json';

    public function __toArray(): array
    {
        $mime = $this->mimetype;

        return [
            $mime => [
                'description' => '',
            ],
        ];
    }
}
