<?php

namespace App\Annotation\OA\Schema;

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

    public function __toArray(): array
    {
        $schema = [
            'type'       => $this->type,
            'properties' => [],
        ];

        foreach ($this->properties as $property) {
            $schema['properties'][$property->name] = $property;
            unset($property->name);
        }

        if (!empty($this->description)) {
            $schema['description'] = $this->description;
        }

        return $schema;
    }
}
