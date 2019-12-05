<?php

declare(strict_types=1);

namespace App\Repository;

use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\Skos;
use App\Ontology\SkosXl;
use App\OpenSkos\OpenSkosIriFactory;
use App\Rdf\Iri;
use App\Rdf\Literal\Literal;
use App\Rdf\Sparql\Client as RdfClient;
use App\Rdf\Triple;
use App\Solr\SolrClient;
use App\Solr\SolrQueryBuilder;
use Doctrine\DBAL\Connection;
use Exception;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Result;
use Solarium\QueryType\Update\Query\Document;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;

abstract class AbstractSolrRepository extends AbstractRepository
{
    /**
     * These namespaces will be indexed to solr, if the value contains a language
     * it will be added to the fieldname as.
     *
     * s_notation (never has an language)
     * s_prefLabel_nl
     * s_prefLabel_en
     *
     * @TODO We now have old fields mapping + this one. Don't need them both.
     *
     * @var array
     */
    //->setFields(['uri', 'prefLabel', 'inScheme', 'scopeNote', 'status'])
    protected $mapping = [
        Skos::PREF_LABEL             => ['s_prefLabel', 't_prefLabel', 'a_prefLabel', 'sort_s_prefLabel', 'prefLabel'],
        Skos::ALT_LABEL              => ['s_altLabel', 't_altLabel', 'a_altLabel', 'sort_s_altLabel'],
        Skos::HIDDEN_LABEL           => ['s_hiddenLabel', 't_hiddenLabel', 'a_hiddenLabel', 'sort_s_hiddenLabel'],
        Skos::DEFINITION             => ['t_definition', 'a_definition', 'definition'],
        Skos::EXAMPLE                => ['t_example', 'a_example'],
        Skos::CHANGE_NOTE            => ['t_changeNote', 'a_changeNote'],
        Skos::EDITORIAL_NOTE         => ['t_editorialNote', 'a_editorialNote'],
        Skos::HISTORY_NOTE           => ['t_historyNote', 'a_historyNote'],
        Skos::SCOPE_NOTE             => ['t_scopeNote', 'a_scopeNote', 'scopeNote'],
        Skos::NOTATION               => ['s_notation', 't_notation', 'a_notation', 'max_numeric_notation'],
        Skos::IN_SCHEME              => ['s_inScheme', 'inScheme'],
        OpenSkos::IN_SKOS_COLLECTION => ['s_inSkosCollection', 'inSkosCollection'],
        OpenSkos::STATUS             => ['s_status', 'status'],
        OpenSkos::SET                => ['s_set'],
        OpenSkos::TENANT             => ['s_tenant'],
        OpenSkos::UUID               => ['s_uuid'],
        OpenSkos::TO_BE_CHECKED      => ['b_toBeChecked'],
        DcTerms::CREATOR             => ['s_creator'],
        DcTerms::DATE_SUBMITTED      => ['d_dateSubmited'],
        DcTerms::CONTRIBUTOR         => ['s_contributor'],
        DcTerms::MODIFIED            => ['d_modified', 'sort_d_modified_earliest'],
        OpenSkos::ACCEPTED_BY        => ['s_acceptedBy'],
        DcTerms::DATE_ACCEPTED       => ['d_dateAccepted'],
        OpenSkos::DELETED_BY         => ['s_deletedBy'],
        OpenSkos::DATE_DELETED       => ['d_dateDeleted'],
        SkosXl::LITERAL_FORM         => ['a_skosXlLiteralForm'],
        Rdf::TYPE                    => ['s_rdfType'],
        SkosXl::PREF_LABEL           => ['s_prefLabelXl'],
        SkosXl::ALT_LABEL            => ['s_altLabelXl'],
        SkosXl::HIDDEN_LABEL         => ['s_hiddenLabelXl'],
    ];

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

    /**
     * @param Triple[] $triples
     */
    public function insertTriples(array $triples): \EasyRdf_Http_Response
    {
        $client      = $this->solrClient->getClient();
        /** @var UpdateQuery $updateQuery */
        $updateQuery = $client->createUpdate();
        $grouped     = $this->skosRepository::groupTriples($triples);
        if (!count($grouped)) {
            die('TODO: throw exception');
        }

        foreach ($grouped as $uri => $triples) {
            /** @var Document $document */
            $document = $updateQuery->createDocument();
            $document->setField('uri', $uri);

            foreach ($triples as $triple) {
                $predicate = $triple->getPredicate()->getUri();
                if (!isset($this->mapping[$predicate])) {
                    continue;
                }

                $value = $triple->getObject();
                if ($value instanceof Iri) {
                    $value = $value->getUri();
                }
                if ($value instanceof Literal) {
                    $value = $value->value();
                }

                $fields = $this->mapping[$predicate];
                foreach ($fields as $field) {
                    $document->addField($field, $value);
                }
            }

            $updateQuery->addDocument($document);
        }

        $updateQuery->addCommit(true);
        $client->update($updateQuery);

        return parent::insertTriples($triples);
    }
}
