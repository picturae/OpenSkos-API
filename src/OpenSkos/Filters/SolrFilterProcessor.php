<?php

declare(strict_types=1);

namespace App\OpenSkos\Filters;

use App\Annotation\Error;
use App\Annotation\ErrorInherit;
use App\EasyRdf\EasyRdfClient;
use App\Exception\ApiException;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Rdf\Iri;
use App\Rdf\Sparql\SparqlQuery;
use App\Solr\ParserText;
use Doctrine\DBAL\Connection;

final class SolrFilterProcessor
{
    //Filter Types
    const TYPE_URI    = 'uri';
    const TYPE_UUID   = 'uuid';
    const TYPE_STRING = 'string';

    //Group for filter
    const ENTITY_INSTITUTION   = 'institution';
    const ENTITY_SET           = 'set';
    const ENTITY_CONCEPTSCHEME = 'conceptscheme';
    const VALUE_STATUS         = 'status';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EasyRdfClient
     */
    private $rdfClient;


    /**
     * @var FilterProcessorHelper
     */
    private $filter_helper;

    /**
     * SolrFilterProcessor constructor.
     * @param Connection $connection
     * @param EasyRdfClient $rdfClient
     * @param FilterProcessorHelper $filter_helper
     */
    public function __construct(Connection $connection, EasyRdfClient $rdfClient, FilterProcessorHelper $filter_helper)
    {
        $this->connection = $connection;
        $this->rdfClient = $rdfClient;
        $this->filter_helper = $filter_helper;
    }


    /**
     * @param $uuid
     *
     * @psalm-suppress UndefinedInterfaceMethod
     *
     * @ErrorInherit(class=SparqlQuery::class, method="SelectSubjectFromUuid")
     */
    private function retrieveUriFromUuid($uuid): ?string
    {
        $sparql = SparqlQuery::SelectSubjectFromUuid(
            $uuid
        );
        $graph = $this->rdfClient->fetch($sparql);

        /*
         * @psalm-suppress UndefinedInterfaceMethod
         */
        if (is_iterable($graph) && iterator_count($graph) > 0) {
            $resource = $graph[0];

            return $resource->subject->getUri();
        }

        return null;
    }

    /**
     * @param $value
     *
     * @return bool
     */
    public static function isUuid($value)
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
     * @return bool
     */
    public function hasPublisher(array $filters)
    {
        $has_publisher = isset($filters['publisherFilter']);

        return $has_publisher;
    }


    /**
     * @param array $filterList
     * @param bool $resolve_publisher if true, resolve a publisher uri to a tenant code. (This involves an extra Jena query)
     *
     * @return array
     *
     * @throws ApiException
     * @Error(code="solrfilterprocessor-build-institutions-filters-uuid-not-supported",
     *        status=400,
     *        description="The search by UUID for institutions could not be retrieved (Predicate is not used in Jena Store)."
     * )
     * @Error(code="solrfilterprocessor-build-institutions-filters-search-by-string",
     *        status=400,
     *        description="The search by string for sets could not be retrieved (Predicate is not used in Jena Store)."
     */
    public function buildInstitutionFilters(array $filterList, bool $resolve_publisher = false):array
    {
        $dataOut = [];

        foreach ($filterList as $filter) {
            if (self::isUuid($filter)) {
                throw new ApiException('solrfilterprocessor-build-institutions-filters-uuid-not-supported');
            } elseif (filter_var($filter, FILTER_VALIDATE_URL)) {
                if (true === $resolve_publisher) {
                    $code      = $this->filter_helper->resolveInstitutionsToCode(new Iri($filter));
                    $dataOut[] = $code;
                } else {
                    throw new ApiException('solrfilterprocessor-build-institutions-filters-search-by-string');
                }
            } else {
                $dataOut[] = $filter;
            }
        }

        return $dataOut;
    }

    /**
     * @param array $filterList
     * @param bool $resolve_code
     * @return array
     *
     * @throws ApiException
     * @Error(code="solrfilterprocessor-build-set-filters-uuid-not-supported",
     *        status=400,
     *        description="The search by UUID for sets could not be retrieved (Predicate is not used in Jena Store)."
     * )
     * @Error(code="solrfilterprocessor-build-set-filters-search-by-string",
     *        status=400,
     *        description="The search by string for sets could not be retrieved (Predicate is not used in Jena Store)."
     * )
     *
     * @Error(code="solrfilterprocessor-build-set-filters-search-by-string",
     *        status=400,
     *        description="The search by string for sets could not be retrieved (Predicate is not used in Jena Store)."
     * )
     *
     * @ErrorInherit(class=SolrFilterProcessor::class, method="isUuid")
     */
    public function buildSetFilters(array $filterList, bool $resolve_code = false):array
    {
        $dataOut = [];

        foreach ($filterList as $filter) {
            if (self::isUuid($filter)) {
                throw new ApiException('solrfilterprocessor-build-set-filters-uuid-not-supported');
            } elseif (filter_var($filter, FILTER_VALIDATE_URL)) {
                $dataOut[] = ['predicate' => OpenSkos::SET, 'value' => $filter, 'type' => self::TYPE_URI, 'entity' => self::ENTITY_SET];
            } else {
                if (true === $resolve_code) {
                    $code      = $this->filter_helper->resolveSetToUriLiteral($filter);
                    $dataOut[] = $code;
                } else {
                    throw new ApiException('solrfilterprocessor-build-set-filters-search-by-string');
                }
            }
        }

        return $dataOut;
    }
    /**
     * @return array
     *
     * @Error(code="solrfilterprocessor-build-conceptscheme-filters-search-by-string",
     *        status=400,
     *        description="The search by string for concept schemes could not be retrieved (Predicate is not used in Jena Store)."
     * )
     *
     * @ErrorInherit(class=SolrFilterProcessor::class, method="retrieveUriFromUuid")
     */
    public function buildConceptSchemeFilters(array $filterList)
    {
        $dataOut = [];

        $nIdx             = 0;
        $filtersAsStrings = [];

        foreach ($filterList as $filter) {
            if (self::isUuid($filter)) {
                $uriAsString = $this->retrieveUriFromUuid($filter);
                if (isset($uriAsString)) {
                    $filtersAsStrings[] = sprintf('s_inScheme:"%s"', $uriAsString);
                } else {
                    //Make sure this filter term doesn't match anything. Other logical-ORed terms may still work.
                    $filtersAsStrings[] = 's_inScheme:"xxxxxxxxxxxxxxxxxxxx"';
                }
            } elseif (filter_var($filter, FILTER_VALIDATE_URL)) {
                $filtersAsStrings[] = sprintf('s_inScheme:"%s"', $filter);
            } else {
                throw new ApiException('solrfilterprocessor-build-conceptscheme-filters-search-by-string');
            }
        }

        if (count($filtersAsStrings)) {
            $dataOut['conceptschemeFilter'] = sprintf('( %s )', join(' OR ', $filtersAsStrings));
        }

        return $dataOut;
    }

    /**
     * @return array
     *
     * @Error(code="solrfilterprocessor-build-statuses-filters-unrecognised-status",
     *        status=400,
     *        description="Unrecognised status in filters.",
     *        fields={"received","accepted"}
     * )
     */
    public function buildStatusesFilters(array $filterList)
    {
        $acceptableStatuses = ['none', 'candidate', 'approved', 'redirected', 'not_compliant', 'rejected', 'obsolete', 'deleted'];
        $dataOut            = [];

        $nIdx             = 0;
        $filtersAsStrings = [];

        foreach ($filterList as $filter) {
            if (!in_array($filter, $acceptableStatuses, true)) {
                throw new ApiException('solrfilterprocessor-build-statuses-filters-unrecognised-status', [
                    'received' => $filter,
                    'accepted' => $acceptableStatuses,
                ]);
            }
            if ('none' === $filter) {
                //'none' turns up in some search profiles. I have no idea how it got there.
                continue;
            } else {
                $filtersAsStrings[] = sprintf('s_status:"%s"', $filter);
            }
        }

        if (count($filtersAsStrings)) {
            $dataOut['statusesFilter'] = sprintf('( %s )', join(' OR ', $filtersAsStrings));
        }

        return $dataOut;
    }

    /**
     * @return array
     */
    public function buildUserFilters(array $filterList)
    {
        $userParams = [
            'creator'             => 's_creator',
            'openskos:acceptedBy' => 's_acceptedBy',
            /* The following fields are copied from OpenSkos 2.2, but do not seem to work in solr! */
            'openskos:modifiedBy' => 's_contributor',
            'openskos:deletedBy'  => 's_deletedBy',
        ];

        $dataOut = [];

        $filtersAsStrings = [];

        foreach ($userParams as $param => $solrField) {
            if (isset($filterList[$param])) {
                foreach ($filterList[$param] as $value) {
                    if (filter_var($value, FILTER_VALIDATE_URL)) {
                        $filtersAsStrings[] = sprintf('%s:"%s"', $solrField, $value);
                    }
                }
                //@TODO: Retrieve URI from ID. Implement in ticket #39357.
            }
        }

        if (count($filtersAsStrings)) {
            $dataOut['usersFilter'] = sprintf('( %s )', join(' OR ', $filtersAsStrings));
        }

        return $dataOut;
    }

    /**
     * @return array
     */
    public function buildInteractionsFilters(array $filterList)
    {
        $dateParams = ['dateSubmitted', 'modified', 'dateAccepted', 'openskos:deleted'];

        $userParams = ['creator', 'openskos:modifiedBy', 'openskos:acceptedBy', 'openskos:deletedBy'];

        $parser = new ParserText();

        $dateParams = [
            'dateSubmitted'    => 'd_dateSubmitted',
            'modified'         => 'd_modified',
            'dateAccepted'     => 'd_dateAccepted',
            'openskos:deleted' => 'd_dateDeleted',
        ];

        $dataOut = [];

        $nIdx             = 0;
        $filtersAsStrings = [];

        foreach ($dateParams as $param => $solrField) {
            if (isset($filterList[$param])) {
                $dateQuery = $parser->buildDatePeriodQuery(
                    $solrField,
                    $filterList[$param]['from'] ?? null,
                    $filterList[$param]['until'] ?? null
            );
                $filtersAsStrings[] = $dateQuery;
            }
        }

        if (count($filtersAsStrings)) {
            $dataOut['interactionsFilter'] = sprintf('( %s )', join(' OR ', $filtersAsStrings));
        }

        return $dataOut;
    }
}
