<?php

declare(strict_types=1);

namespace App\Repository;

use App\Annotation\Document;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Sparql\Client;
use App\Rdf\Iri;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Connection;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var string
     */
    const DOCUMENT_CLASS = null;

    /**
     * @var string
     */
    const DOCUMENT_TYPE = null;

    /**
     * @var Client
     */
    protected $rdfClient;

    /**
     * @var SkosResourceRepository<AbstractRdfDocument>
     */
    protected $skosRepository;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var OpenSkosIriFactory
     */
    protected $iriFactory;

    /**
     * @var array
     */
    protected $annotations = [];

    /**
     * AbstractRepository constructor.
     *
     * @param Client             $rdfClient
     * @param OpenSkosIriFactory $iriFactory
     */
    public function __construct(
        Client $rdfClient,
        OpenSkosIriFactory $iriFactory,
        Connection $connection
    ) {
        $this->rdfClient = $rdfClient;

        $this->connection = $connection;
        $repository = $this;

        // Fetch all annotations
        $annotationReader = new AnnotationReader();
        $documentReflection = new \ReflectionClass(static::DOCUMENT_CLASS);
        $documentAnnotations = $annotationReader->getClassAnnotations($documentReflection);

        // Loop through annotations and extract data
        foreach ($documentAnnotations as $annotation) {
            if ($annotation instanceof Document\Table) {
                $this->annotations['table'] = $annotation->value;
            }
            if ($annotation instanceof Document\Type) {
                $this->annotations['type'] = $annotation->value;
            }
            if ($annotation instanceof Document\UUID) {
                $this->annotations['uuid'] = $annotation->value;
            }
        }

        $this->skosRepository = new SkosResourceRepository(
            function (Iri $iri, array $triples) use ($repository): AbstractRdfDocument {
                return call_user_func(static::DOCUMENT_CLASS.'::fromTriples', $iri, $triples, $repository);
            },
            $this->rdfClient
        );

        $this->iriFactory = $iriFactory;
    }

    /**
     * Returns the connection for this repository.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param int   $offset
     * @param int   $limit
     * @param array $filters
     *
     * @return array
     */
    public function all(int $offset = 0, int $limit = 100, array $filters = []): array
    {
        $results = $this->skosRepository->allOfType(
            new Iri(static::DOCUMENT_TYPE),
            $offset,
            $limit,
            $filters
        );

        if (!empty($this->annotations['table'])) {
            $repository = $this;
            $connection = $this->getConnection();
            $documentClass = static::DOCUMENT_CLASS;
            $documentType = static::DOCUMENT_TYPE;

            foreach ($results as $user) {
                $uri = $user->getResource()->iri()->getUri();

                // Fetch more data from the database
                $stmt = $connection
                    ->createQueryBuilder()
                    ->select('*')
                    ->from($this->annotations['table'], 't')
                    ->where('t.uri = :uri')
                    ->setParameter(':uri', $uri)
                    ->execute();
                if (is_int($stmt)) {
                    return [];
                }
                $rawDocument = $stmt->fetch();

                // Attach fetched data to the document
                $user->populate($rawDocument);
            }
        }

        return $results;
    }

    /**
     * @param Iri $iri
     *
     * @return AbstractRdfDocument|null
     */
    public function findByIri(Iri $iri): ?AbstractRdfDocument
    {
        return $this->skosRepository->findByIri($iri);
    }

    /**
     * @param InternalResourceId $id
     *
     * @return AbstractRdfDocument|null
     */
    public function find(InternalResourceId $id): ?AbstractRdfDocument
    {
        return $this->findByIri($this->iriFactory->fromInternalResourceId($id));
    }

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return array|null
     */
    public function findBy(Iri $predicate, InternalResourceId $object): ?array
    {
        return $this->skosRepository->findBy(new Iri(static::DOCUMENT_TYPE), $predicate, $object);
    }

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return AbstractRdfDocument|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?AbstractRdfDocument
    {
        if (empty($this->annotations['table'])) {
            $res = $this->skosRepository->findOneBy(new Iri(static::DOCUMENT_TYPE), $predicate, $object);
        } else {
            $documentClass = static::DOCUMENT_CLASS;
            $documentMapping = $documentClass::getMapping();
            $documentReverseMapping = array_flip($documentMapping);

            $fieldUri = $predicate->getUri();
            $column = $documentReverseMapping[$fieldUri];

            $stmt = $this->getConnection()
                ->createQueryBuilder()
                ->select('*')
                ->from($this->annotations['table'], 't')
                ->where("t.${column} = :${column}")
                ->setParameter(":${column}", $object->id())
                ->setMaxResults(1)
                ->execute();

            if (is_int($stmt)) {
                return null;
            }

            $data = $stmt->fetchAll();
            if (!count($data)) {
                return null;
            }
            $data = $data[0];

            $subject = new Iri('http://'.($this->annotations['table']).'/'.$data[$this->annotations['uuid']]);

            return $documentClass::fromRelational($subject, $data, $this);
        }

        return $res;
    }

    /**
     * @return array
     */
    public function getAnnotations(): array
    {
        return $this->annotations;
    }
}
