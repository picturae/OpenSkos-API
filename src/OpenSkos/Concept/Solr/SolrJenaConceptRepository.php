<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept\Solr;

use App\Ontology\Skos;
use App\OpenSkos\Concept\Concept;
use App\OpenSkos\Concept\ConceptRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\OpenSkos\SkosResourceRepositoryWithProjection;
use App\Rdf\Iri;
use App\Rdf\Sparql\Client as RdfClient;
use App\Solr\SolrClient;
use Exception;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Result;

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
     * @var SkosResourceRepositoryWithProjection
     */
    private $skosRepository;

    /**
     * @var OpenSkosIriFactory
     */
    private $iriFactory;

    /**
     * @var SolrQueryBuilder
     */
    private $solrQueryBuilder;

    /**
     * SparqlConceptRepository constructor.
     */
    public function __construct(
        RdfClient $rdfClient,
        SolrClient $solrClient,
        OpenSkosIriFactory $iriFactory,
        SolrQueryBuilder $solrQueryBuilder
    ) {
        $this->rdfClient        = $rdfClient;
        $this->solrClient       = $solrClient;
        $this->solrQueryBuilder = $solrQueryBuilder;

        $this->skosRepository = new SkosResourceRepositoryWithProjection(
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
     * @param string $query
     * @param int    &$numFound     output Total number of found records
     * @param array  $sorts
     * @param bool   $full_retrieve
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
        $numFound   = $solrResult->getNumFound();

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
     * @throws Exception
     */
    public function all(int $offset = 0, int $limit = 100, array $filters = [], array $projection = []): array
    {
        $numfound = 0;

        $conceptFilter = array_merge($filters,
            [
                'rdfTypeFilter' => sprintf('s_rdfType:"%s"', Skos::CONCEPT),
            ]
        );

        $matchingIris = $this->search('*:*', $limit, $offset, $numfound, null, $conceptFilter);

        if (0 !== count($matchingIris)) {
            if (0 !== count($projection)) {
                $data = $this->skosRepository->findManyByIriListWithProjection($matchingIris, $projection);
            } else {
                $data = $this->skosRepository->findManyByIriList($matchingIris);
            }
        } else {
            $data = [];
        }

        return $data;
    }

    /**
     * This is my documentation.
     *
     * @throws Exception
     */
    public function fullSolrSearch(string $searchTerm, int $offset = 0, int $limit = 100, array $filters = [], array $selection = [], array $projection = []): array
    {
        $numfound = 0;

        $conceptFilter = array_merge($filters,
            [
                'rdfTypeFilter' => sprintf('s_rdfType:"%s"', Skos::CONCEPT),
            ]
        );

        //TODO: Projection Params
        $searchExpression = $this->solrQueryBuilder->processSearchExpression($searchTerm, $selection);

        $matchingIris = $this->search($searchExpression, $limit, $offset, $numfound, null, $conceptFilter);

        if (0 !== count($matchingIris)) {
            $data = $this->skosRepository->findManyByIriListWithProjection($matchingIris, $projection);
        } else {
            $data = [];
        }

        return $data;
    }

    public function findByIri(Iri $iri): ?Concept
    {
        return $this->skosRepository->findByIri($iri);
    }

    public function find(InternalResourceId $id): ?Concept
    {
        return $this->findByIri($this->iriFactory->fromInternalResourceId($id));
    }

    public function findBy(Iri $predicate, InternalResourceId $object): ?array
    {
        return $this->skosRepository->findBy(new Iri(Skos::CONCEPT), $predicate, $object);
    }

    public function findOneBy(Iri $predicate, InternalResourceId $object): ?Concept
    {
        $res = $this->skosRepository->findOneBy(new Iri(Skos::CONCEPT), $predicate, $object);

        return $res;
    }
}
