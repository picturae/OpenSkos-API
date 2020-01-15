<?php

namespace App\Annotation\OA\Schema;

use App\Ontology\Context;
use App\Rdf\AbstractRdfDocument;

/**
 * @Annotation
 */
class ObjectLiteral extends Literal
{
    public $type = 'object';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var mixed
     */
    public $properties = [];

    /**
     * @var mixed
     */
    public $class;

    public function __toArray(): array
    {
        if (isset($this->class) && class_exists($this->class)) {
            if (is_a($this->class, AbstractRdfDocument::class, true)) {
                $annotations      = $this->class::annotations();

                // Known fields
                $idLiteral            = new StringLiteral();
                $idLiteral->name      = '@id';
                $typeLiteral          = new StringLiteral();
                $typeLiteral->name    = '@type';
                $typeLiteral->example = implode(':', Context::decodeUri($annotations['document-type'])??[]);
                $this->properties     = [$idLiteral, $typeLiteral];

                // Use mapping to fetch known fields from ontology
                foreach ($this->class::getMapping() as $localName => $predicate) {
                    // TODO: Hidden Fields?

                    $decoded = Context::decodeUri($predicate);
                    $type    = Context::literaltype($predicate);
                    if (is_null($decoded)) {
                        continue;
                    }
                    $short = implode(':', $decoded);
                    switch ($type) {
                        case 'xsd:boolean':
                            $literal       = new BooleanLiteral();
                            $literal->name = $short;
                            array_push($this->properties, $literal);
                            break;
                        case 'xsd:string':
                            $literal       = new StringLiteral();
                            $literal->name = $short;
                            array_push($this->properties, $literal);
                            break;
                    }
                }
            } else {
                $reflectionClass      = new \ReflectionClass($this->class);
                $reflectionProperties = $reflectionClass->getProperties();
                die('TODO: generic properties into swagger');
            }
        }

        $schema = [
            'type'       => $this->type,
            'properties' => [],
        ];

        foreach ($this->properties as $property) {
            $schema['properties'][$property->name] = $property;
        }

        if (!empty($this->name)) {
            $schema['name'] = $this->name;
        }

        if (!empty($this->in)) {
            $schema['in'] = $this->in;
        }

        if (!empty($this->description)) {
            $schema['description'] = $this->description;
        }

        return $schema;
    }
}
