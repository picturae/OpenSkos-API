<?php

declare(strict_types=1);

namespace App\OpenSkos;

use App\OpenSkos\Institution\Exception\UnknownProperty;
use App\OpenSkos\Institution\Institution;
use App\Rdf\Iri;
use App\Rdf\Literal\Literal;
use App\Rdf\RdfResource;
use App\Rdf\Triple;

abstract class SkosResource implements RdfResource
{
    /**
     * @var Triple[]
     */
    protected $triples = [];
    /**
     * @var Iri
     */
    protected $subject;
    /**
     * @var array<string,?Literal>
     */
    protected $literals;

    /**
     * @param Iri      $iri
     * @param Triple[] $triples
     *
     * @return Institution
     */
    public static function fromTriples(Iri $iri, array $triples)
    {
        $iriString = $iri->getUri();
        $obj = new Institution($iri);
        foreach ($triples as $triple) {
            if ($triple->getSubject()->getUri() !== $iriString) {
                // TODO: Should we skip, log or throw an exception?
                continue;
            }

            $predicateString = $triple->getPredicate()->getUri();
            if (false === array_key_exists($predicateString, $obj->literals)) {
                // TODO: Should we skip, log or throw an exception?
                continue;
            }

            $obj->triples[] = $triple;
            $object = $triple->getObject();
            if ($object instanceof Literal) {
                $obj->literals[$predicateString] = $object;
            }
            //TODO: Add Resources
        }

        return $obj;
    }

    public function iri(): Iri
    {
        return $this->subject;
    }

    public function triples(): array
    {
        return $this->triples;
    }

    public function addLiteral(Iri $property, Literal $literal): void
    {
        $iri = $property->getUri();
        if (!array_key_exists($iri, $this->literals)) {
            throw new UnknownProperty($property);
        }

        $this->literals[$iri] = $literal;

        $this->triples[] = new Triple(
            $this->subject,
            $property,
            $literal
        );
    }
}
