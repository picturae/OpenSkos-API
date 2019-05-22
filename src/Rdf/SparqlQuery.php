<?php

declare(strict_types=1);

namespace App\Rdf;

final class SparqlQuery
{
    /**
     * @var string
     */
    private $sparql;
    /**
     * @var array
     */
    private $variables;

    public function __construct(
        string $sparql,
        array $variables = []
    ) {
        $this->sparql = $sparql;
        $this->variables = $variables;
    }

    public function rawSparql(): string
    {
        //TODO: replace variables?
        return $this->sparql;
    }
}
