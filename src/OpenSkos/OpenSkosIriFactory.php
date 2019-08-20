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
        //TODO: These are all being constructed as services from .yaml with http://tenant as the namespace, so why is
        // this not broken? Can we ditch it?

        return new Iri($this->namespace.'/'.$id->id());
    }
}
