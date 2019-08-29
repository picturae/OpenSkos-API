<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept\Solr;

use App\Ontology\Skos;
use App\OpenSkos\Concept\Concept;
use App\OpenSkos\Concept\ConceptRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepository;
use App\Solr\SolrClient;
use App\Rdf\Sparql\Client as RdfClient;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Result;
use App\Rdf\Iri;
use Exception;

final class SolrJenaConceptRepository implements ConceptRepository
{
    /**
     * @var SolrClient
     */
    private $solrClient;

    /**
     * @var RdfClient
     */
    private $rdfClient;

    /**
     * @var SkosResourceRepository<Concept>
     */
    private $skosRepository;

    /**
     * @var OpenSkosIriFactory
     */
    private $iriFactory;

    /**
     * SparqlConceptRepository constructor.
     *
     * @param RdfClient          $rdfClient
     * @param SolrClient         $solrClient
     * @param OpenSkosIriFactory $iriFactory
     */
    public function __construct(
        RdfClient $rdfClient,
        SolrClient $solrClient,
        OpenSkosIriFactory $iriFactory
    ) {
        $this->rdfClient = $rdfClient;
        $this->solrClient = $solrClient;

        $this->skosRepository = new SkosResourceRepository(
            function (Iri $iri, array $triples): Concept {
                return Concept::fromTriples($iri, $triples);
            },
            $this->rdfClient
        );

        $this->iriFactory = $iriFactory;
    }

    /**
     * Perform a full text query
     * lucene / solr queries are possible
     * for the available fields see schema.xml.
     *
     * @param string     $query
     * @param int        $rows
     * @param int        $start
     * @param int        &$numFound     output Total number of found records
     * @param array      $sorts
     * @param array|null $filterQueries
     * @param bool       $full_retrieve
     *
     * @return array Array of uris
     *
     * @throws Exception
     */
    public function search(
        $query,
        int $rows = 20,
        int $start = 0,
        ?int &$numFound = 0,
        $sorts = null,
        array $filterQueries = null,
        $full_retrieve = false
    ) {
        $client = $this->solrClient->getClient();

        /** @var Query $select */
        $select = $client->createSelect();
        $select->setStart($start)
            ->setRows($rows)
            ->setFields(['uri', 'prefLabel', 'inScheme', 'scopeNote', 'status'])
            ->setQuery($query);
        if (!empty($sorts)) {
            $select->setSorts($sorts);
        }

        if (!empty($filterQueries)) {
            foreach ($filterQueries as $key => $value) {
                $select->addFilterQuery($select->createFilterQuery($key)->setQuery($value));
            }
        }

        /** @var Result $solrResult */
        $solrResult = $client->select($select);
        $numFound = $solrResult->getNumFound();

        $return_data = [];

        if ($full_retrieve) {
            //Return an array of URI's
            $return_data = $solrResult->getDocuments();
        } else {
            //Return an array of URI's
            foreach ($solrResult as $doc) {
                $return_data[] = $doc->uri;
            }
        }

        return $return_data;
    }

    /**
     * @param int   $offset
     * @param int   $limit
     * @param array $filters
     *
     * @return array
     *
     * @throws Exception
     */
    public function all(int $offset = 0, int $limit = 100, array $filters = []): array
    {
        $numfound = 0;

        $conceptFilter = array_merge($filters,
            [
                'rdfTypeFilter' => sprintf('s_rdfType:"%s"', Skos::CONCEPT),
            ]
        );

        $matchingIris = $this->search('*:*', $limit, $offset, $numfound, null, $conceptFilter);

        if (0 !== count($matchingIris)) {
            $data = $this->skosRepository->findManyByIriList($matchingIris);
        } else {
            $data = [];
        }

        return $data;
    }

    /**
     * @param Iri $iri
     *
     * @return Concept|null
     */
    public function findByIri(Iri $iri): ?Concept
    {
        return $this->skosRepository->findByIri($iri);
    }

    /**
     * @param InternalResourceId $id
     *
     * @return Concept|null
     */
    public function find(InternalResourceId $id): ?Concept
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
        return $this->skosRepository->findBy(new Iri(Skos::CONCEPT), $predicate, $object);
    }

    /**
     * @param Iri                $predicate
     * @param InternalResourceId $object
     *
     * @return Concept|null
     */
    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Concept
    {
        $res = $this->skosRepository->findOneBy(new Iri(Skos::CONCEPT), $predicate, $object);

        return $res;
    }
}
