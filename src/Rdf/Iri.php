<?php

declare(strict_types=1);

namespace App\Rdf;

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
     */
    public function __construct($value)
    {
        if (is_string($value)) {
            $this->uri = $value;
        } elseif ($value instanceof Iri) {
            $this->uri = $value->getUri();
        } else {
            $exceptionString = 'Invalid type for $value, expected string|Iri but got ';
            $type            = gettype($value);
            if ('object' === $type) {
                $type .= '('.get_class($value).')';
            }
            throw new \Exception($exceptionString.$type);
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
