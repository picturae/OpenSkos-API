<?php

namespace App\Rdf;

use App\Ontology\Rdf;
use App\OpenSkos\Label\Label;
use App\OpenSkos\Label\LabelRepository;
use App\Rdf\Literal\Literal;
use App\Rdf\Literal\StringLiteral;
use App\Repository\AbstractRepository;

abstract class AbstractRdfDocument implements RdfResource
{
    const id = 'id';

    /**
     * @var string[]
     */
    protected static $mapping = [];

    /**
     * Mapping between different-named fields between local and doctrine.
     *
     * The key   = doctrine
     * The value = local
     *
     * @var array
     */
    protected static $uniqueFields = null;

    /**
     * The key   = doctrine
     * The value = local.
     *
     * @var array
     */
    protected static $columnAlias = [];

    /**
     * Fields to be ignored when importing through doctrine.
     *
     * @var string[]
     */
    protected static $ignoreFields = [];

    /**
     * @var AbstractRepository|null
     */
    protected $repository = null;

    /**
     * @var VocabularyAwareResource
     */
    protected $resource;

    /**
     * @param Iri                     $subject
     * @param VocabularyAwareResource $resource
     * @param AbstractRepository      $repository
     */
    public function __construct(
        Iri $subject,
        ?VocabularyAwareResource $resource = null,
        ?AbstractRepository $repository = null
    ) {
        if (is_null($resource)) {
            $this->resource = new VocabularyAwareResource($subject, static::$mapping);
        } else {
            $this->resource = $resource;
        }

        if (!is_null($repository)) {
            $this->repository = $repository;
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
     * @param mixed $property
     * @param mixed $value
     *
     * @return bool
     */
    public function addProperty($property, $value): bool
    {
        if (is_string($property)) {
            if (!array_key_exists($property, static::$mapping)) {
                return false;
            }
        }

        if ($value instanceof RdfTerm) {
            if ($property instanceof Iri) {
                $iri = $property;
            } else {
                $mapped = static::$mapping['property'];
                if (is_null($mapped)) {
                    return false;
                }
                $iri = new Iri($mapped);
            }
            $this->getResource()->addProperty($iri, $value);

            return true;
        }

        if (is_string($value)) {
            if ($property instanceof Iri) {
                $iri = $property;
            } else {
                $mapped = static::$mapping[$property];
                if (is_null($mapped)) {
                    return false;
                }
                $iri = new Iri($mapped);
            }
            $term = new StringLiteral($value);
            $this->getResource()->addProperty($iri, $term);

            return true;
        }

        return false;
    }

    /**
     * @param string $property
     *
     * @return array|null
     */
    public function getMappedProperty(string $property): ?array
    {
        return $this->getProperty($this->getMapping()[$property]);
    }

    /**
     * @param string $property
     *
     * @return string[]|string|null
     */
    public function getMappedValue(string $property)
    {
        $literals = $this->getProperty($this->getMapping()[$property]);

        if (is_null($literals)) {
            return null;
        }

        $literals = array_map(function (Literal $literal) {
            return $literal->__toString();
        }, array_values($literals));

        if (!count($literals)) {
            return null;
        }

        if (1 === count($literals)) {
            return $literals[0];
        }

        return $literals;
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
     * @param Iri                $subject
     * @param Triple[]           $triples
     * @param AbstractRepository $repository
     *
     * @return AbstractRdfDocument
     */
    public static function fromTriples(Iri $subject, array $triples, AbstractRepository $repository = null): self
    {
        $resource = VocabularyAwareResource::fromTriples($subject, $triples, static::$mapping);

        return new static($subject, $resource, $repository);
    }

    /**
     * @param Iri                $subject
     * @param array              $data
     * @param AbstractRepository $repository
     *
     * @return AbstractRdfDocument
     */
    public static function fromRelational(Iri $subject, ?array $data, AbstractRepository $repository = null): self
    {
        $document = new static($subject, null, $repository);

        if (is_array($data)) {
            $document->populate($data);
        }

        $type = $document->getProperty(Rdf::TYPE);
        if (is_array($type) && (!count($type))) {
            $type = null;
        }

        if (is_null($type) && (!is_null($repository))) {
            $document->addProperty(new Iri(Rdf::TYPE), new Iri($repository->getAnnotations()['type']));
        }

        return $document;
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

    public function populate(array $data = null): bool
    {
        if (is_null($data)) {
            return false;
        }

        // Iterate over data and inject into resource
        $resource = $this->getResource();
        foreach ($data as $column => $value) {
            if (in_array($column, static::$ignoreFields, true)) {
                continue;
            }
            if (array_key_exists($column, static::$columnAlias)) {
                $column = static::$columnAlias[$column];
            }
            if (!array_key_exists($column, static::$mapping)) {
                continue;
            }
            if (is_null($value)) {
                continue;
            }

            $iri = new Iri(static::$mapping[$column]);
            if (Rdf::TYPE === static::$mapping[$column]) {
                $term = new Iri($value);
            } else {
                $term = new StringLiteral($value);
            }
            $resource->addProperty($iri, $term);
        }

        return true;
    }
}
