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
     */
    public function __construct(string $value)
    {
        $this->uri = $value;
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

    public function ntripleString(): string
    {
        return "<$this->uri>";
    }
}
