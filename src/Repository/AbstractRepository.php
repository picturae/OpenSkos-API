<?php

declare(strict_types=1);

namespace App\Repository;

use App\Annotation\Document;
use App\EasyRdf\TripleFactory;
use App\Ontology\Rdf;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepositoryWithProjection;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Iri;
use App\Rdf\Literal\Literal;
use App\Rdf\Literal\StringLiteral;
use App\Rdf\RdfTerm;
use App\Rdf\Sparql\Client;
use App\Rdf\Sparql\SparqlQuery;
use App\Rdf\Triple;
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
     * @var SkosResourceRepositoryWithProjection
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
     * @var callable
     */
    protected $tripleFactory;

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
        $this->rdfClient  = $rdfClient;
        $this->connection = $connection;
        $repository       = $this;

        /*
         * Fallback factory
         * Builds an array from the triples with _id as the identifier (like in mongo).
         *
         * @param Iri      $iri
         * @param Triple[] $triples
         *
         * @return array
         */
        $this->tripleFactory = function (Iri $iri, array $triples): array {
            $result = ['_id' => $iri->getUri()];
            foreach ($triples as $triple) {
                $result[(string) $triple->getPredicate()] = $triple->getObject();
            }

            return $result;
        };

        if (static::DOCUMENT_CLASS) {
            $this->annotations = call_user_func([static::DOCUMENT_CLASS, 'annotations']);

            /*
             * Builds an object from the given triples.
             *
             * @param Iri      $iri
             * @param Triple[] $triples
             *
             * @return AbstractRdfDocument
             */
            $this->tripleFactory = function (Iri $iri, array $triples) use ($repository): AbstractRdfDocument {
                return call_user_func(static::DOCUMENT_CLASS.'::fromTriples', $iri, $triples, $repository);
            };
        }

        $this->skosRepository = new SkosResourceRepositoryWithProjection(
            $this->tripleFactory,
            $this->rdfClient
        );

        $this->iriFactory = $iriFactory;
    }

    public function fromGraph(\EasyRdf_Graph $graph): ?array
    {
        $tripleFactory = $this->tripleFactory;
        $triples       = TripleFactory::triplesFromGraph($graph);
        $grouped       = $this->skosRepository::groupTriples($triples);
        foreach ($grouped as $subject => $triples) {
            $grouped[$subject] = $tripleFactory(new Iri($subject), $triples);
        }

        return $grouped;
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

        if (!empty($this->annotations['document-table'])) {
            $repository    = $this;
            $connection    = $this->getConnection();
            $documentClass = static::DOCUMENT_CLASS;
            $documentType  = static::DOCUMENT_TYPE;

            foreach ($results as $user) {
                $uri = $user->getResource()->iri()->getUri();

                // Fetch more data from the database
                $stmt = $connection
                    ->createQueryBuilder()
                    ->select('*')
                    ->from($this->annotations['document-table'], 't')
                    ->where('t.'.($this->annotations['document-uuid']).' = :uuid')
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

    /**
     * @return AbstractRdfDocument|null
     */
    public function findByIri(Iri $iri)
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

        if (!empty($this->annotations['document-table'])) {
            $documentClass          = static::DOCUMENT_CLASS;
            $documentMapping        = $documentClass::getMapping();
            $documentReverseMapping = array_flip($documentMapping);

            $uri = $res->getResource()->iri()->getUri();

            // Fetch more data from the database
            $stmt = $this->getConnection()
                ->createQueryBuilder()
                ->select('*')
                ->from($this->annotations['document-table'], 't')
                ->where('t.'.($this->annotations['document-uuid']).' = :uuid')
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

    public function getByUuid(InternalResourceId $uuid)
    {
        return $this->skosRepository->getByUuid($uuid);
    }

    // TODO: Copy doctrine data fetch into other functions
    // TODO: Delete this function
    public function get(Iri $object)
    {
        $res = $this->skosRepository->findByIri($object);

        // No resource = done
        if (is_null($res)) {
            return null;
        }

        if (!empty($this->annotations['document-table'])) {
            $documentClass          = static::DOCUMENT_CLASS;
            $documentMapping        = $documentClass::getMapping();
            $documentReverseMapping = array_flip($documentMapping);

            $column = $this->annotations['document-uuid'];

            $stmt = $this->getConnection()
                ->createQueryBuilder()
                ->select('*')
                ->from($this->annotations['document-table'], 't')
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

    /**
     * @param Iri|Literal|InternalResourceId|string $object
     */
    public function findSubjectForObject(
        $object
    ): array {
        $client = $this->skosRepository->rdfClient();
        $sparql = SparqlQuery::selectSubjectFromObject(
            $object
        );

        if (is_string($object)) {
            $object = new StringLiteral($object);
        }
        if (!($object instanceof RdfTerm)) {
            return [];
        }

        $triples = [];
        foreach ($client->fetch($sparql) as $row) {
            $iri = new Iri($row->subject->getUri());
            array_push($triples, new Triple(
                $iri,
                new Iri($row->predicate->getUri()),
                $object
            ));
            array_push($triples, new Triple(
                $iri,
                new Iri(Rdf::TYPE),
                new Iri($row->type->getUri())
            ));
        }

        return $triples;
    }

    /**
     * @param Triple[] $triples
     */
    public function insertTriples(array $triples): \EasyRdf_Http_Response
    {
        return $this->skosRepository->insertTriples($triples);
    }

    public function delete(Iri $subject)
    {
        return $this->skosRepository->deleteSubject($subject);
    }
}
