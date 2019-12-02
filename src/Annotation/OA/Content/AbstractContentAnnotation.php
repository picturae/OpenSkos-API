<?php

namespace App\Annotation\OA\Content;

use App\Annotation\AbstractAnnotation;

abstract class AbstractContentAnnotation extends AbstractAnnotation
{
    public function __toArray(): array
    {
        return [];
    }
}
