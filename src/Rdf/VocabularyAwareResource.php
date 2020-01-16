<?php

declare(strict_types=1);

namespace App\Rdf;

use App\Annotation\Error;
use App\Annotation\ErrorInherit;
use App\Exception\ApiException;

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
     * @var array
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
     * @param Triple[]             $triples
     * @param array<string,string> $mapping
     *
     * @Error(code="vocabularyawareresource-from-triples-mapping-not-array",
     *        status=500,
     *        description="VocabularyAwareResource needs a (array)mapping.",
     *        fields={"receivedType"}
     * )
     *
     * @ErrorInherit(class=Iri::class   , method="getUri"      )
     * @ErrorInherit(class=Triple::class, method="getObject"   )
     * @ErrorInherit(class=Triple::class, method="getPredicate")
     * @ErrorInherit(class=Triple::class, method="getSubject"  )
     */
    public static function fromTriples(Iri $iri, array $triples, array $mapping = null): VocabularyAwareResource
    {
        if (!is_array($mapping)) {
            throw new ApiException('vocabularyawareresource-from-triples-mapping-not-array', [
                'receivedType' => gettype($mapping),
            ]);
        }

        $iriString = $iri->getUri();
        $obj       = new self($iri, $mapping);
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
            if (!isset($obj->properties[$predicateString])) {
                $obj->properties[$predicateString] = [];
            }
            $obj->properties[$predicateString][] = $triple->getObject();
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

    /**
     * @return RdfTerm[][]|null
     */
    public function properties(): ?array
    {
        return $this->properties;
    }

    public function getProperty(string $property): ?array
    {
        return $this->properties[$property] ?? [];
    }

    /**
     * @Error(code="vocabularyawareresource-addproperty-unknown-property",
     *        status=500,
     *        description="Property is not expected",
     *        fields={"property"}
     * )
     *
     * @ErrorInherit(class=Iri::class   , method="getUri"     )
     * @ErrorInherit(class=Triple::class, method="__construct")
     */
    public function addProperty(Iri $property, RdfTerm $object): void
    {
        $iri = $property->getUri();
        if (!array_key_exists($iri, $this->properties)) {
            throw new ApiException('vocabularyawareresource-addproperty-unknown-property', [
                'property' => $property,
            ]);
        }

        $this->properties[$iri][] = $object;

        $this->triples[] = new Triple(
            $this->subject,
            $property,
            $object
        );
    }

    /**
     * @param string|null $value
     *
     * @ErrorInherit(class=Iri::class   , method="getUri"      )
     * @ErrorInherit(class=Triple::class, method="__toString"  )
     * @ErrorInherit(class=Triple::class, method="getObject"   )
     * @ErrorInherit(class=Triple::class, method="getPredicate")
     */
    public function removeTriple(string $predicate, $value = null): int
    {
        $numberRemoved = 0;

        /* We have 2 things to delete. First the triples */
        foreach ($this->triples as $key => $triple) {
            $triplePred = $triple->getPredicate()->getUri();
            if ($triplePred === $predicate) {
                if ((!isset($value)) || $value === $triple->getObject()) {
                    unset($this->triples[$key]);
                    ++$numberRemoved;
                }
            }
        }
        $this->triples = array_values($this->triples);

        /* Then the properties */
        if (isset($this->properties[$predicate])) {
            if (isset($value)) {
                foreach ($this->properties[$predicate] as $key => $triple) {
                    if ($value === $triple->__toString()) {
                        unset($this->properties[$predicate][$key]);
                    }
                }
                if (0 === count($this->properties[$predicate])) {
                    unset($this->properties[$predicate]);
                }
            } else {
                $this->properties[$predicate] = null;
            }
        }

        return $numberRemoved;
    }

    /**
     * @param $tripleKey
     * @param $newValue
     */
    public function replaceTriple($tripleKey, $newValue): void
    {
        $this->triples[$tripleKey] = $newValue;
    }

    public function reIndexTripleStore(): void
    {
        $this->triples = array_values($this->triples);
    }
}
