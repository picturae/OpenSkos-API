<?php

declare(strict_types=1);

namespace App\OpenSkos\Label;

use App\Ontology\DcTerms;
use App\Ontology\Rdf;
use App\Ontology\SkosXl;
use App\Rdf\VocabularyAwareResource;
use App\Rdf\Iri;
use App\Rdf\RdfResource;
use App\Rdf\Triple;

final class Label implements RdfResource
{
    const type = 'type';
    const modified = 'modified';
    const literalForm = 'literalForm';

    /**
     * @var string[]
     */
    private static $mapping = [
        self::type => Rdf::TYPE,
        self::modified => DcTerms::MODIFIED,
        self::literalForm => SkosXl::LITERALFORM,
    ];

    /**
     * @var VocabularyAwareResource
     */
    private $resource;

    /**
     * @var Iri|null
     */
    private $type;

    /**
     * @var Iri|null
     */
    private $subject;

    /**
     * @var Iri
     */
    private $childSubject;

    /**
     * When we create a label, the terms 'subject', 'predicate', 'object' can get mixed up, because the object of the parent is
     * also the subject of the label elements.
     *
     * We allow the parent subject and predicate to be null because
     * 1) In theory labels can exist in isolation.
     * 2) The way the repositories are structured means the code the fetches label elements is unaware of which parent
     *      it's fetching for. Indeed: it can be fetched for several parents
     *
     * Label constructor.
     *
     * @param Iri                          $childSubject
     * @param VocabularyAwareResource|null $resource
     * @param Iri|null                     $parentPredicate
     * @param Iri|null                     $parentSubject
     */
    private function __construct(
        Iri $childSubject,
        ?VocabularyAwareResource $resource = null,
        ?Iri $parentPredicate = null,
        ?Iri $parentSubject = null
    ) {
        if (null === $resource) {
            $this->resource = new VocabularyAwareResource($childSubject, array_flip(self::$mapping));
        } else {
            $this->resource = $resource;
        }
        $this->childSubject = $childSubject;
        $this->type = $parentPredicate;
        $this->subject = $parentSubject;
    }

    /**
     * @return Iri
     */
    public function iri(): Iri
    {
        return $this->resource->iri();
    }

    /**
     * @return Triple[]
     */
    public function triples(): array
    {
        return $this->resource->triples();
    }

    /**
     * @param Iri $subject
     *
     * @return Label
     */
    public static function createEmpty(Iri $subject): self
    {
        return new self($subject);
    }

    /**
     * @param Iri      $subject
     * @param Triple[] $triples
     *
     * @return Label
     */
    public static function fromTriples(Iri $subject, array $triples): self
    {
        $resource = VocabularyAwareResource::fromTriples($subject, $triples, self::$mapping);

        return new self($subject, $resource);
    }

    /**
     * @return Iri|null
     */
    public function getType(): ?Iri
    {
        return $this->type;
    }

    /**
     * @param Iri|null $type
     */
    public function setType(?Iri $type): void
    {
        $this->type = $type;
    }

    /**
     * @return Iri|null
     */
    public function getSubject(): ?Iri
    {
        return $this->subject;
    }

    /**
     * @param Iri $subject|null
     */
    public function setSubject(Iri $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return Iri
     */
    public function getChildSubject(): Iri
    {
        return $this->childSubject;
    }

    /**
     * @param Iri $childSubject
     */
    public function setChildSubject(Iri $childSubject): void
    {
        $this->childSubject = $childSubject;
    }
}
