<?php

namespace App\Rdf;

use App\Annotation\AbstractAnnotation;
use App\Annotation\Error;
use App\Ontology\Context;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\OpenSkos\Label\Label;
use App\OpenSkos\Label\LabelRepository;
use App\Rdf\Literal\Literal;
use App\Rdf\Literal\StringLiteral;
use App\Repository\AbstractRepository;
use Doctrine\Common\Annotations\AnnotationReader;

abstract class AbstractRdfDocument implements RdfResource
{
    const id = 'id';

    /**
     * @var string[]
     */
    protected static $mapping = [];

    /**
     * @var string[]
     */
    protected static $required = [];

    /**
     * Mapping between different-named fields between local and doctrine.
     *
     * The key   = doctrine
     * The value = local
     *
     * @var array|null
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
     * These are field names BEFORE running aliases.
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

        // Auto-fill rdf:type
        $annotations = static::annotations();
        if (is_null($this->getValue(Rdf::TYPE)) && isset($annotations['document-type'])) {
            $this->addProperty(new Iri(Rdf::TYPE), new Iri($annotations['document-type']));
        }

        if (!is_null($repository)) {
            $this->repository = $repository;
        }

        // Auto-fill uuid
        $uuid = $this->getValue(OpenSkos::UUID);
        if (!($uuid instanceof StringLiteral)) {
            $iri    = $this->iri()->getUri();
            $tokens = explode('/', $iri);
            $uuid   = array_pop($tokens);
            if (self::isUuid($uuid)) {
                $this->addProperty(new Iri(OpenSkos::UUID), $uuid);
            }
        }
    }

    public static function annotations(): array
    {
        static $annotations = [];

        // Build cache if needed
        if (!isset($annotations[static::class])) {
            // Fetch all annotations
            $annotationReader    = new AnnotationReader();
            $documentReflection  = new \ReflectionClass(static::class);
            $documentAnnotations = $annotationReader->getClassAnnotations($documentReflection);

            // Loop through annotations and extract data
            foreach ($documentAnnotations as $annotation) {
                $annotationName = str_replace('\\', '-', strtolower(get_class($annotation)));
                if ($annotation instanceof AbstractAnnotation) {
                    $annotationName = $annotation->name();
                }

                $annotations[static::class][$annotationName] = $annotation->value;
            }
        }

        return $annotations[static::class];
    }

    /**
     * @param mixed $value
     */
    private static function isUuid($value): bool
    {
        $retval = false;

        if (is_string($value) &&
            36 == strlen($value) &&
            preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $value)) {
            $retval = true;
        }

        return $retval;
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
     * @return RdfTerm[][]
     */
    public function properties(): ?array
    {
        return $this->resource->properties();
    }

    public function getProperty(string $property): ?array
    {
        return $this->resource->getProperty($property);
    }

    public function getValue(string $property): ?RdfTerm
    {
        $properties = $this->getProperty($property);
        if (is_null($properties)) {
            return null;
        }
        foreach ($properties as $entry) {
            return $entry;
        }

        return null;
    }

    /**
     * @param mixed $property
     * @param mixed $value
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

    public function getMappedProperty(string $property): ?array
    {
        return $this->getProperty($this->getMapping()[$property]);
    }

    /**
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
     * @return AbstractRdfDocument
     */
    public static function createEmpty(Iri $subject): self
    {
        return new static($subject);
    }

    /**
     * @param Triple[]           $triples
     * @param AbstractRepository $repository
     */
    public static function fromTriples(Iri $subject, array $triples, AbstractRepository $repository = null): AbstractRdfDocument
    {
        $resource = VocabularyAwareResource::fromTriples($subject, $triples, static::$mapping);

        return new static($subject, $resource, $repository);
    }

    /**
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
            $document->addProperty(new Iri(Rdf::TYPE), new Iri(static::annotations()['document-type']));
        }

        return $document;
    }

    /**
     * Loads the XL labels and replaces the default URI value with the full resource.
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
                    /** @var Iri */
                    $xlLabel = $triple->getObject();

                    /** @var Label */
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

    /**
     * Whether or not the resource already exists in our sparql db.
     */
    public function exists(): bool
    {
        // No repository = can't check
        if (is_null($this->repository)) {
            return false;
        }

        // Attempt to fetch the resource
        $iri   = $this->resource->iri();
        $found = $this->repository->findByIri($iri);

        return !is_null($found);
    }

    /**
     * Returns a list of errors with the current resource.
     *
     * @Error(code="abstract-rdf-document-missing-predicate",
     *        status=400,
     *        description="A required predicate for this RDF resource is missing",
     *        fields={"predicate"}
     * )
     * @Error(code="abstract-rdf-document-invalid-resource-type",
     *        status=400,
     *        description="The given resource type does not match the configured resource type for this endpoint"
     * )
     * @Error(code="abstract-rdf-document-corrupt-rdf-resource-properties-null",
     *        status=400,
     *        description="Properties could not be loaded from the rdf resource"
     * )
     */
    public function errors(string $errorPrefix = null): array
    {
        $errors      = [];
        $annotations = static::annotations();
        $errorPrefix = $errorPrefix ?? 'abstract-rdf-document';

        // Verify document type
        if (isset($annotations['document-type'])) {
            $type = $this->getValue(Rdf::TYPE);
            if (is_null($type)) {
                array_push($errors, [
                    'code' => $errorPrefix.'-missing-predicate',
                    'data' => [
                        'predicate' => Rdf::TYPE,
                    ],
                ]);
            } elseif (($type instanceof Iri) && ($type->getUri() !== $annotations['document-type'])) {
                array_push($errors, [
                    'code' => $errorPrefix.'-invalid-resource-type',
                    'data' => [
                        'expected' => $annotations['document-type'],
                        'actual'   => $type->getUri(),
                    ],
                ]);
            }
        }

        // Ensure required fields
        foreach (static::$required as $requiredPredicate) {
            $found = $this->getValue($requiredPredicate);
            if (is_null($found)) {
                array_push($errors, [
                    'code' => $errorPrefix.'-missing-predicate',
                    'data' => [
                        'predicate' => $requiredPredicate,
                    ],
                ]);
            }
        }

        // Validate each field to it's config
        $properties = $this->properties();
        if (is_null($properties)) {
            // No properties = error
            array_push($errors, [
                'code' => $errorPrefix.'-corrupt-rdf-resource-properties-null',
            ]);
        } else {
            foreach ($properties as $predicate => $propertyList) {
                // Split uri into namespace:field
                $tokens = Context::decodeUri($predicate);
                if (is_null($tokens) || is_null($propertyList)) {
                    continue;
                }

                // Detect the namespace class and the field's datatype
                $namespace = Context::namespaces[$tokens[0]];

                // Check if namespace::validateField exists
                $method = 'validate'.ucfirst($tokens[1]);
                if (!method_exists($namespace, $method)) {
                    continue;
                }

                // Loop through all properties for this predicate
                foreach ($propertyList as $property) {
                    // Check the value for errors
                    $error = call_user_func([$namespace, 'validate'.ucfirst($tokens[1])], $property);
                    if (is_null($error)) {
                        continue;
                    }

                    // Push errors to our list
                    array_push($errors, $error);
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * @Error(code="rdf-document-delete-missing-repository",
     *        status=500,
     *        description="No repository is known to the document requested to be deleted"
     * )
     */
    public function delete(): ?array
    {
        // We need to repository to save
        if (is_null($this->repository)) {
            return [[
                'code' => 'rdf-document-delete-missing-repository',
            ]];
        }

        $this->repository->delete($this->iri());

        return null;
    }

    /**
     * @Error(code="rdf-document-update-missing-repository",
     *        status=500,
     *        description="No repository is known to the document requested to be updated"
     * )
     * @Error(code="rdf-document-update-does-not-exist",
     *        status=404,
     *        description="The document requested to be updated does not exist",
     *        fields={"iri"}
     * )
     */
    public function update(): ?array
    {
        // No repository = can't check
        if (is_null($this->repository)) {
            return [[
                'code' => 'rdf-document-update-missing-repository',
            ]];
        }

        // Fetch the resource
        $iri   = $this->resource->iri();
        $found = $this->repository->findByIri($iri);
        if (!$found) {
            return [[
                'code' => 'rdf-document-update-does-not-exist',
                'data' => [
                    'iri' => $iri->getUri(),
                ],
            ]];
        }

        // TODO: check updatefields

        // Delete everything
        $deleteErrors = $this->delete();
        if ($deleteErrors) {
            return $deleteErrors;
        }

        // Re-insert the document
        $saveErrors = $this->save();
        if ($saveErrors) {
            return $saveErrors;
        }

        return null;
    }

    /**
     * @Error(code="rdf-document-save-missing-repository",
     *        status=500,
     *        description="No repository is known to the document requested to be saved"
     * )
     * @Error(code="rdf-document-save-exception",
     *        status=500,
     *        description="The insert threw an exception",
     *        fields={"message"}
     * )
     */
    public function save(): ?array
    {
        // Refuse to save if there are errors
        $errors = $this->errors();
        if (count($errors)) {
            return $errors;
        }

        // TODO: delete old data?

        // We need to repository to save
        if (is_null($this->repository)) {
            return [[
                'code' => 'rdf-document-save-missing-repository',
            ]];
        }

        try {
            $this->repository->insertTriples($this->triples());

            return null;
        } catch (\Exception $e) {
            return [[
                'code' => 'save-failed',
                'data' => [
                    'message' => $e->getMessage(),
                ],
            ]];
        }
    }
}
