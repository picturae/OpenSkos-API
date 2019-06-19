<?php

declare(strict_types=1);

namespace App\Rdf\Exception;

use App\Rdf\Sparql\SparqlQuery;

class InvalidSparqlQuery extends ClientException
{
    /**
     * @var \App\Rdf\Sparql\SparqlQuery|null
     */
    private $query;

    public static function causedBy(SparqlQuery $query, string $message): self
    {
        $obj = new self($message);
        $obj->query = $query;

        return $obj;
    }
}
