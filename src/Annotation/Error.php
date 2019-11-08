<?php

namespace App\Annotation;

/**
 * @Annotation
 */
class Error extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $code = '';

    /**
     * @var array
     */
    public $fields = [];

    /**
     * @var string
     */
    public $description = '';

    /**
     * Returns a list of all found usages of the error annotation throughout the application.
     */
    public function usages(): array
    {
        return [
            'openskos-tenant-validate-literal-type' => [
                'class' => 'App\Ontology\OpenSkos',
                'method' => 'validateTenant',
                'code' => 'openskos-tenant-validate-literal-type',
                'description' => 'Indicates the object for the tenant predicate has a different type than \'http://www.w3.org/2001/XMLSchema#string\'',
                'fields' => [
                    'expected',
                    'actual',
                ],
            ],
            'openskos-uuid-validate-literal-type' => [
                'class' => 'App\Ontology\OpenSkos',
                'method' => 'validateUuid',
                'code' => 'openskos-uuid-validate-literal-type',
                'description' => 'Indicates the object for the uuid predicate has a different type than \'http://www.w3.org/2001/XMLSchema#string\'',
                'fields' => [
                    'expected',
                    'actual',
                ],
            ],
            'openskos-uuid-validate-regex' => [
                'class' => 'App\Ontology\OpenSkos',
                'method' => 'validateUuid',
                'code' => 'openskos-uuid-validate-regex',
                'description' => 'Indicates the object for the uuid predicate did not match the configured regex',
                'fields' => [
                    'regex',
                    'value',
                ],
            ],
            'openskos-disablesearchinothertenants-validate-literal-type' => [
                'class' => 'App\Ontology\OpenSkos',
                'method' => 'validateDisableSearchInOtherTenants',
                'code' => 'openskos-disablesearchinothertenants-validate-literal-type',
                'description' => 'Indicates the object for the disablesearchinothertenants predicate has a different type than \'http://www.w3.org/2001/XMLSchema#boolean\'',
                'fields' => [
                    'expected',
                    'actual',
                ],
            ],
        ];
    }
}
