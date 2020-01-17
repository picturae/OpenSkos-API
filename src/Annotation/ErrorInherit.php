<?php

namespace App\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * @Annotation
 */
class ErrorInherit extends AbstractAnnotation
{
    /**
     * @var class-string
     */
    public $class;

    /**
     * @var string
     */
    public $method = '';

    public function getErrors()
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
        $reflectionClass  = new \ReflectionClass($this->class);
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
