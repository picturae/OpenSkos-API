<?php

namespace App\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * @Annotation
 */
class ErrorInherit extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $class = '';

    /**
     * @var string
     */
    public $method = '';

    public function getErrors(): array
    {
        // Build annotationreader once
        static $annotationReader = null;
        if (is_null($annotationReader)) {
            $annotationReader = new AnnotationReader();
        }

        // No config = no errors
        if (!(strlen($this->class)||strlen($this->method))) {
            return [];
        }

        // Fetch the method's annotations
        /** @var class-string $classname */
        $classname        = $this->class;
        $reflectionClass  = new \ReflectionClass($classname);
        $reflectionMethod = $reflectionClass->getMethod($this->method);
        $annotations      = $annotationReader->getMethodAnnotations($reflectionMethod);

        // Start building a list
        $errors = [];
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Error) {
                array_push($errors, $annotation);
                continue;
            }

            if ($annotation instanceof ErrorInherit) {
                $errors = array_merge($errors, $annotation->getErrors());
                continue;
            }

            // Not interesting
        }

        // Return built list
        return $errors;
    }
}
