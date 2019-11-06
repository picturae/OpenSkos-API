<?php

declare(strict_types=1);

namespace App\Rdf;

interface RdfResource
{
    public function iri(): Iri;

    public function triples(): array;

    /**
     * TODO: as soon as PHP7.4 is declared stable, add return type
     *   There's an open bug in 7.0 through 7.3, preventing returntype self from working nicely.
     *
     * @param Triple[] $triples
     *
     * @return RdfResource
     */
    public static function fromTriples(Iri $subject, array $triples);
}
