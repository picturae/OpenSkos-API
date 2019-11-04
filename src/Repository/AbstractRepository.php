<?php

declare(strict_types=1);

namespace App\Repository;

use App\Annotation\Document;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Iri;
use App\Rdf\Sparql\Client;
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
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

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
                    ->where('t.'.($this->annotations['uuid']).' = :uuid')
                    ->setParameter(':uuid', $uri)
                    ->execute();
                if (is_int($stmt)) {
                    return [];
                }
                $rawDocument = $stmt->fetch();

                // No data = skip
                if (!is_array($rawDocument)) {
                    continue;
                }

                // Attach fetched data to the document
                $user->populate($rawDocument);
            }
        }

        return $results;
    }

    public function findByIri(Iri $iri): ?AbstractRdfDocument
    {
        return $this->skosRepository->findByIri($iri);
    }

    public function find(InternalResourceId $id): ?AbstractRdfDocument
    {
        return $this->findByIri($this->iriFactory->fromInternalResourceId($id));
    }

    public function findBy(Iri $predicate, InternalResourceId $object): ?array
    {
        return $this->skosRepository->findBy(new Iri(static::DOCUMENT_TYPE), $predicate, $object);
    }

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?AbstractRdfDocument
    {
        /** @var AbstractRdfDocument $res */
        $res = $this->skosRepository->findOneBy(new Iri(static::DOCUMENT_TYPE), $predicate, $object);

        // No resource = done
        if (is_null($res)) {
            return null;
        }

        if (!empty($this->annotations['table'])) {
            $documentClass = static::DOCUMENT_CLASS;
            $documentMapping = $documentClass::getMapping();
            $documentReverseMapping = array_flip($documentMapping);

            $uri = $res->getResource()->iri()->getUri();

            // Fetch more data from the database
            $stmt = $this->getConnection()
                ->createQueryBuilder()
                ->select('*')
                ->from($this->annotations['table'], 't')
                ->where('t.'.($this->annotations['uuid']).' = :uuid')
                ->setParameter(':uuid', $uri)
                ->execute();

            if (is_int($stmt)) {
                return $res;
            }
            $rawDocument = $stmt->fetch();

            // No data = skip
            if (!is_array($rawDocument)) {
                return $res;
            }

            // Attach fetched data to the document
            $res->populate($rawDocument);
        }

        return $res;
    }

    public function get(Iri $object)
    {
        $res = $this->skosRepository->get($object);

        // No resource = done
        if (is_null($res)) {
            return null;
        }

        if (!empty($this->annotations['table'])) {
            $documentClass = static::DOCUMENT_CLASS;
            $documentMapping = $documentClass::getMapping();
            $documentReverseMapping = array_flip($documentMapping);

            $column = $this->annotations['uuid'];

            $stmt = $this->getConnection()
                ->createQueryBuilder()
                ->select('*')
                ->from($this->annotations['table'], 't')
                ->where("t.${column} = :uuid")
                ->setParameter(':uuid', $res->getResource()->iri()->getUri())
                ->setMaxResults(1)
                ->execute();

            if (is_int($stmt)) {
                return $res;
            }

            $data = $stmt->fetch();
            if (!is_array($data)) {
                return $res;
            }

            $res->populate($data);
        }

        return $res;
    }

    public function getAnnotations(): array
    {
        return $this->annotations;
    }
}
