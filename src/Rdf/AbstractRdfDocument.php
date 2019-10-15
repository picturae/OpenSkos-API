<?php

namespace App\Rdf;

use App\OpenSkos\Label\Label;
use App\OpenSkos\Label\LabelRepository;

abstract class AbstractRdfDocument implements RdfResource
{
    const id = 'id';

    /**
     * @var string[]
     */
    protected static $mapping = null;

    /**
     * @var VocabularyAwareResource
     */
    protected $resource;

    protected function __construct(
        Iri $subject,
        ?VocabularyAwareResource $resource = null
    ) {
        if (null === $resource) {
            $this->resource = new VocabularyAwareResource($subject, array_flip(static::$mapping));
        } else {
            $this->resource = $resource;
        }
    }

    /**
     * @return string[]
     */
    public static function getMapping(): array
    {
        return static::$mapping;
    }

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
     * @return Triple[]
     */
    public function properties(): ?array
    {
        return $this->resource->properties();
    }

    /**
     * @param string $property
     *
     * @return array|null
     */
    public function getProperty(string $property): ?array
    {
        return $this->resource->getProperty($property);
    }

    /**
     * @param Iri $subject
     *
     * @return AbstractRdfDocument
     */
    public static function createEmpty(Iri $subject): self
    {
        return new static($subject);
    }

    /**
     * @param Iri      $subject
     * @param Triple[] $triples
     *
     * @return AbstractRdfDocument
     */
    public static function fromTriples(Iri $subject, array $triples): self
    {
        $resource = VocabularyAwareResource::fromTriples($subject, $triples, static::$mapping);

        return new static($subject, $resource);
    }

    /**
     * Loads the XL labels and replaces the default URI value with the full resource.
     *
     * @param LabelRepository $labelRepository
     */
    public function loadFullXlLabels(LabelRepository $labelRepository): void
    {
        $tripleList = $this->triples();
        foreach ($tripleList as $triplesKey => $triple) {
            if ($triple instanceof Label) {
                continue;
            }

            foreach ($this::$xlPredicates as $key => $xlLabelPredicate) {
                if ($triple->getPredicate()->getUri() == $xlLabelPredicate) {
                    /**
                     * @var Iri
                     */
                    $xlLabel = $triple->getObject();

                    $fullLabel = $labelRepository->findByIri($xlLabel);
                    if (isset($fullLabel)) {
                        $subject = $triple->getSubject();
                        $fullLabel->setSubject($subject);
                        $predicate = $triple->getPredicate();
                        $fullLabel->setType($predicate);
                        $this->resource->replaceTriple($triplesKey, $fullLabel);
                    }
                }
            }
        }
        $this->resource->reIndexTripleStore();
    }

    /**
     * We are returning by reference, to quickly enable the data-levels functionality.
     *   Otherwise, a lot of extra hoops have to be jumped through just to add a data level.
     *
     * @return VocabularyAwareResource
     */
    public function &getResource(): VocabularyAwareResource
    {
        return $this->resource;
    }
}
