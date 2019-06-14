<?php

declare(strict_types=1);

namespace App\Rdf;

abstract class TripleSet
{
    /**
     * @var Iri
     */
    protected $subject;

    /**
     * @var Triple[]
     */
    protected $triples = [];

    /**
     * @var Literal[]
     */
    protected $properties = [];

    /**
     * @return Iri
     */
    public function getSubject(): Iri
    {
        return $this->subject;
    }

    public function count(): int
    {
        return count($this->properties);
    }

    abstract public function getLevel2Predicate();

    /**
     * @return Triple[]
     */
    abstract public static function getMapping(): array;

    public function getLevel2Object(): ?Literal
    {
        $object = null;
        $mappingKey = $this->getLevel2Predicate();
        if ($mappingKey) {
            $object = $this->properties[$mappingKey];
        }

        return $object;
    }

    /**
     * @param Iri      $subject
     * @param Triple[] $triples
     *
     * @return Institution
     */
    public static function fromTriples(Iri $subject, array $triples): TripleSet
    {
        $class = get_called_class();

        $invMapping = array_flip($class::getMapping());

        /**
         * var Literal[].
         */
        $properties = [];

        foreach ($triples as $triple) {
            // Skip unrelated triples
            if ($triple->getSubject()->getUri() !== $subject->getUri()) {
                continue;
            }

            $property = $invMapping[$triple->getPredicate()->getUri()] ?? null;
            // Skip unknown properties
            if (null === $property) {
                continue;
            }

            $object = $triple->getObject();
            $properties[$property] = $object;
        }

        $obj = new $class($subject);
        $obj->properties = $properties;

        return $obj;
    }

    /**
     * @return Literal[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return Triple[]
     */
    public function triples(): array
    {
        return $this->triples;
    }
}
