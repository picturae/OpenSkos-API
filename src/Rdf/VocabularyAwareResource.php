<?php

declare(strict_types=1);

namespace App\Rdf;

use App\Rdf\Exception\UnknownProperty;

final class VocabularyAwareResource implements RdfResource
{
    /**
     * @var Triple[]
     */
    private $triples = [];
    /**
     * @var Iri
     */
    private $subject;
    /**
     * @var array<string,?RdfTerm>
     */
    private $properties;

    /**
     * @var array<string>
     */
    private $vocabulary;

    public function __construct(Iri $subject, array $vocabulary)
    {
        $this->subject = $subject;

        $this->properties = array_fill_keys(
            $vocabulary,
            null
        );

        $this->vocabulary = $vocabulary;
    }

    /**
     * @param Iri                  $iri
     * @param Triple[]             $triples
     * @param array<string,string> $mapping
     *
     * @return static
     */
    public static function fromTriples(Iri $iri, array $triples, array $mapping)
    {
        $iriString = $iri->getUri();
        $obj = new self($iri, $mapping);
        foreach ($triples as $triple) {
            if ($triple->getSubject()->getUri() !== $iriString) {
                // TODO: Should we skip, log or throw an exception?
                continue;
            }

            $predicateString = $triple->getPredicate()->getUri();
            if (false === array_key_exists($predicateString, $obj->properties)) {
                continue;
            }
            $obj->triples[] = $triple;
            $obj->properties[$predicateString] = $triple->getObject();
        }

        return $obj;
    }

    public function iri(): Iri
    {
        return $this->subject;
    }

    /**
     * @return Triple[]
     */
    public function triples(): array
    {
        return $this->triples;
    }

    public function addProperty(Iri $property, RdfTerm $object): void
    {
        $iri = $property->getUri();
        if (!array_key_exists($iri, $this->properties)) {
            throw new UnknownProperty($property);
        }

        $this->properties[$iri] = $object;

        $this->triples[] = new Triple(
            $this->subject,
            $property,
            $object
        );
    }
}
