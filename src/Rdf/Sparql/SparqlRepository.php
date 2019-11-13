<?php

namespace App\Rdf\Sparql;

use App\Annotation\Document;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\Iri;
use App\Rdf\RdfResource;
use Doctrine\Common\Annotations\AnnotationReader;

class SparqlRepository implements SparqlRepositoryInterface
{
    /**
     * @var Client
     */
    protected $rdfClient;

    /**
     * @var SkosResourceRepository<RdfResource>
     */
    protected $skosRepository;

    /**
     * @var OpenSkosIriFactory
     */
    protected $iriFactory;

    /**
     * @var string
     */
    protected $resourceClass;

    /**
     * @var string
     */
    protected $resourceType;

    /**
     * SparqlRepository constructor.
     *
     * @param string $resourceClass
     * @param string $resourceType
     */
    public function __construct(
        Client $rdfClient,
        OpenSkosIriFactory $iriFactory,
        string $resourceClass = null,
        string $resourceType = null
    ) {
        // No class to work on = not good
        if (is_null($resourceClass)) {
            throw new \Exception('SparqlRepository can not be initialized without resource class');
        }

        // The class we're working for must be an RdfResource
        if (!is_a($resourceClass, RdfResource::class, true)) {
            throw new \Exception("SparqlRepository needs to be initialized for a class extending RdfResource, got: ${resourceClass}");
        }

        // Attempt using annotations if no type was given
        if (is_null($resourceType)) {
            $resourceType = self::getType($resourceClass);
        }

        // No type given = we can not search
        if (is_null($resourceType)) {
            throw new \Exception('SparqlRepository can not be initialized without resource type');
        }

        $this->rdfClient     = $rdfClient;
        $this->resourceClass = $resourceClass;
        $this->resourceType  = $resourceType;

        $this->skosRepository = new SkosResourceRepository(
            function (Iri $iri, array $triples) use ($resourceClass): RdfResource {
                return call_user_func([$resourceClass, 'fromTriples'], $iri, $triples);
            },
            $this->rdfClient
        );

        $this->iriFactory = $iriFactory;
    }

    /**
     * @param class-string $classname
     */
    private static function getType(string $classname): ?string
    {
        // Fetch all annotations
        $annotationReader    = new AnnotationReader();
        $documentReflection  = new \ReflectionClass($classname);
        $documentAnnotations = $annotationReader->getClassAnnotations($documentReflection);

        // Loop through annotations and return the table
        foreach ($documentAnnotations as $annotation) {
            if ($annotation instanceof Document\Type) {
                return $annotation->value;
            }
        }

        return null;
    }

    public function all(int $offset = 0, int $limit = 100, array $filters = []): array
    {
        return $this->skosRepository->allOfType(
            new Iri($this->resourceType),
            $offset,
            $limit,
            $filters
        );
    }

    public function findByIri(Iri $iri): ?RdfResource
    {
        return $this->skosRepository->findByIri($iri);
    }

    public function findManyByIriList(array $iris): array
    {
        return $this->skosRepository->findManyByIriList($iris);
    }

    public function find(InternalResourceId $id): ?RdfResource
    {
        return $this->findByIri($this->iriFactory->fromInternalResourceId($id));
    }

    public function findBy(Iri $predicate, InternalResourceId $object): ?array
    {
        return $this->skosRepository->findBy(new Iri($this->resourceType), $predicate, $object);
    }

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?RdfResource
    {
        return $this->skosRepository->findOneBy(new Iri($this->resourceType), $predicate, $object);
    }

    public function getOneWithoutUuid(InternalResourceId $uuid): ?RdfResource
    {
        return $this->skosRepository->getOneWithoutUuid(new Iri($this->resourceType), $uuid);
    }
}
