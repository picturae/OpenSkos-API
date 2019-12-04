<?php

declare(strict_types=1);

namespace App\Repository;

use App\OpenSkos\OpenSkosIriFactory;
use App\Rdf\Sparql\Client as RdfClient;
use App\Solr\SolrClient;
use App\Solr\SolrQueryBuilder;
use Doctrine\DBAL\Connection;
use Exception;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Result;

abstract class AbstractSolrRepository extends AbstractRepository
{
    /**
     * @var SolrClient
     */
    protected $solrClient;

    /**
     * @var SolrQueryBuilder
     */
    protected $solrQueryBuilder;

    /**
     * AbstractSolrRepository constructor.
     */
    public function __construct(
        RdfClient $rdfClient,
        OpenSkosIriFactory $iriFactory,
        Connection $connection,
        SolrClient $solrClient,
        SolrQueryBuilder $solrQueryBuilder
    ) {
        parent::__construct($rdfClient, $iriFactory, $connection);
        $this->solrClient       = $solrClient;
        $this->solrQueryBuilder = $solrQueryBuilder;
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

        $rdfTypeFilter = array_merge($filters,
            [
                'rdfTypeFilter' => sprintf('s_rdfType:"%s"', static::DOCUMENT_TYPE),
            ]
        );

        $matchingIris = $this->search('*:*', $limit, $offset, $numfound, null, $rdfTypeFilter);

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

        $rdfTypeFilter = array_merge($filters,
            [
                'rdfTypeFilter' => sprintf('s_rdfType:"%s"', static::DOCUMENT_TYPE),
            ]
        );

        //TODO: Projection Params
        $searchExpression = $this->solrQueryBuilder->processSearchExpression($searchTerm, $selection);

        $matchingIris = $this->search($searchExpression, $limit, $offset, $numfound, null, $rdfTypeFilter);

        if (0 !== count($matchingIris)) {
            $data = $this->skosRepository->findManyByIriListWithProjection($matchingIris, $projection);
        } else {
            $data = [];
        }

        return $data;
    }
}
