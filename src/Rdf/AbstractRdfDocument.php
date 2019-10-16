<?php

namespace App\Rdf;

use App\Annotation\Document\Table;
use App\OpenSkos\Label\Label;
use App\OpenSkos\Label\LabelRepository;
use App\Rdf\Literal\Literal;
use App\Rdf\Literal\StringLiteral;
use App\Repository\AbstractRepository;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Connection;

abstract class AbstractRdfDocument implements RdfResource
{
    const id = 'id';

    /**
     * @var string[]
     */
    protected static $mapping = null;

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
    protected function __construct(
        Iri $subject,
        ?VocabularyAwareResource $resource = null,
        ?AbstractRepository $repository = null
    ) {
        if (is_null($resource)) {
            $this->resource = new VocabularyAwareResource($subject, array_flip(static::$mapping));
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

    public function populate(): bool
    {
        // No repository = no extend
        if (is_null($this->repository)) {
            return false;
        }

        // Fetch all annotations
        $annotationReader = new AnnotationReader();
        $documentReflection = new \ReflectionClass(static::class);
        $documentAnnotations = $annotationReader->getClassAnnotations($documentReflection);

        // Data we're extract from the annotations
        $table = null;

        // Loop through annotations and extract data
        foreach ($documentAnnotations as $annotation) {
            if ($annotation instanceof Table) {
                $table = $annotation->value;
            }
        }

        // No table = no populate
        if (is_null($table)) {
            return false;
        }

        // Start on statement
        $connection = $this->repository->getConnection();
        $stmt = $connection
            ->createQueryBuilder()
            ->select('*')
            ->from($table, 't');

        // Add filter
        $expr = $stmt->expr()->andX();
        foreach (static::$uniqueFields as $remote => $local) {
            $value = $this->getMappedValue($local);
            if (is_array($value)) {
                $expr->add($stmt->expr()->in("t.${remote}", ":${local}"));
                $stmt = $stmt->setParameter(":${local}", $value, Connection::PARAM_STR_ARRAY);
            } else {
                $expr->add($stmt->expr()->eq("t.${remote}", ":${local}"));
                $stmt = $stmt->setParameter(":${local}", $value);
            }

            // Remove old local field, it was there to match upon
            if ($remote != $local) {
                $this->getResource()->removeTriple(static::$mapping[$local]);
            }
        }

        // Run the query
        $stmt = $stmt->where($expr);
        $query_result = $stmt->execute();
        if (is_int($query_result)) {
            return false;
        }

        // Fetch found rows
        $results = $query_result->fetchAll();
        $resource = $this->getResource();

        foreach ($results as $row) {
            foreach ($row as $column => $value) {
                if (in_array($column, static::$ignoreFields, true)) {
                    continue;
                }
                if (!array_key_exists($column, static::$mapping)) {
                    continue;
                }
                if (is_null($value)) {
                    continue;
                }

                $iri = new Iri(static::$mapping[$column]);
                $term = new StringLiteral($value);
                $resource->addProperty($iri, $term);
            }
        }

        return true;
    }
}
