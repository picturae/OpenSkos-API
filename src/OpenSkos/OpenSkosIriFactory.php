<?php

declare(strict_types=1);

namespace App\OpenSkos;

use App\Rdf\Iri;

final class OpenSkosIriFactory
{
    /**
     * @var string
     */
    private $namespace;

    public function __construct(
        string $namespace
    ) {
        $this->namespace = rtrim($namespace, '/');
    }

    public function fromInternalResourceId(InternalResourceId $id): Iri
    {
        return new Iri($this->namespace.'/'.$id->id());
    }
}
