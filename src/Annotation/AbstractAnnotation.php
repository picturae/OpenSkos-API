<?php

namespace App\Annotation;

/**
 * @Annotation
 */
abstract class AbstractAnnotation
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $desc;

    public static function name(): string
    {
        $class = static::class;
        $prefix = 'App\\Annotation\\';
        if (substr($class, 0, strlen($prefix)) == $prefix) {
            $class = substr($class, strlen($prefix));
        }

        return str_replace('\\', '-', strtolower($class));
    }
}
