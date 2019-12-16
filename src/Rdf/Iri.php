<?php

declare(strict_types=1);

namespace App\Rdf;

use App\Annotation\Error;
use App\Exception\ApiException;

class Iri implements RdfTerm
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * Literal constructor.
     *
     * @param mixed $value
     *
     * @Error(code="iri-construct-invalid-value-type",
     *        status=500,
     *        description="Invalid type for $value, expected string|Iri",
     *        fields={"receivedType"}
     * )
     */
    public function __construct($value)
    {
        if (is_string($value)) {
            $this->uri = $value;
        } elseif ($value instanceof Iri) {
            $this->uri = $value->getUri();
        } else {
            $type = gettype($value);
            if ('object' === $type) {
                $type .= '('.get_class($value).')';
            }
            throw new ApiException('iri-construct-invalid-value-type', [
                'receivedType' => $type,
            ]);
        }
    }

    /**
     * Output the uri as string.
     */
    public function __toString(): string
    {
        return $this->uri;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): Iri
    {
        $this->uri = $uri;

        return $this;
    }

    public function ntripleString(): string
    {
        return "<$this->uri>";
    }
}
