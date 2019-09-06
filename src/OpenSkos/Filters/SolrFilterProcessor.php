<?php

declare(strict_types=1);

namespace App\OpenSkos\Filters;

use App\EasyRdf\EasyRdfClient;
use App\OpenSkos\Concept\Solr\ParserText;
use App\Rdf\Sparql\SparqlQuery;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class SolrFilterProcessor
{
    //Filter Types
    const TYPE_URI = 'uri';
    const TYPE_UUID = 'uuid';
    const TYPE_STRING = 'string';

    //Group for filter
    const ENTITY_INSTITUTION = 'institution';
    const ENTITY_SET = 'set';
    const ENTITY_CONCEPTSCHEME = 'conceptscheme';
    const VALUE_STATUS = 'status';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EasyRdfClient
     */
    private $rdfClient;

    /**
     * FilterProcessor constructor.
     *
     * @param Connection    $connection
     * @param EasyRdfClient $rdfClient
     */
    public function __construct(
        Connection $connection,
        EasyRdfClient $rdfClient
    ) {
        $this->connection = $connection;
        $this->rdfClient = $rdfClient;
    }

    /**
     * @param $uuid
     *
     * @return string|null
     *
     * @psalm-suppress UndefinedInterfaceMethod
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
     * @param array $filters
     *
     * @return bool
     */
    public function hasPublisher(array $filters)
    {
        $has_publisher = isset($filters['publisherFilter']);

        return $has_publisher;
    }

    /**
     * @param array $filterList
     *
     * @return array
     */
    public function buildInstitutionFilters(array $filterList)
    {
        $dataOut = [];

        foreach ($filterList as $filter) {
            if (self::isUuid($filter)) {
                throw new BadRequestHttpException('The search by UUID for institutions could not be retrieved (Predicate is not used in Jena Store).');
            } elseif (filter_var($filter, FILTER_VALIDATE_URL)) {
                throw new BadRequestHttpException('The search by string for sets could not be retrieved (Predicate is not used in Jena Store).');
            } else {
                $dataOut = [
                    'tenantFilter' => sprintf('s_tenant:"%s"', $filter),
                ];
            }
        }

        return $dataOut;
    }

    /**
     * @param array $filterList
     *
     * @return array
     */
    public function buildSetFilters(array $filterList)
    {
        $dataOut = [];

        foreach ($filterList as $filter) {
            if (self::isUuid($filter)) {
                throw new BadRequestHttpException('The search by UUID for sets could not be retrieved (Predicate is not used in Jena Store).');
            } elseif (filter_var($filter, FILTER_VALIDATE_URL)) {
                $dataOut = [
                    'setFilter' => sprintf('s_set:"%s"', $filter),
                ];
            } else {
                throw new BadRequestHttpException('The search by string for sets could not be retrieved (Predicate is not used in Jena Store).');
            }
        }

        return $dataOut;
    }

    /**
     * @param array $filterList
     *
     * @return array
     */
    public function buildConceptSchemeFilters(array $filterList)
    {
        $dataOut = [];

        $nIdx = 0;
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
                throw new BadRequestHttpException('The search by string for concept schemes could not be retrieved (Predicate is not used in Jena Store).');
            }
        }

        if (count($filtersAsStrings)) {
            $dataOut['conceptschemeFilter'] = sprintf('( %s )', join(' OR ', $filtersAsStrings));
        }

        return $dataOut;
    }

    /**
     * @param array $filterList
     *
     * @return array
     */
    public function buildStatusesFilters(array $filterList)
    {
        $acceptableStatuses = ['none', 'candidate', 'approved', 'redirected', 'not_compliant', 'rejected', 'obsolete', 'deleted'];
        $dataOut = [];

        $nIdx = 0;
        $filtersAsStrings = [];

        foreach ($filterList as $filter) {
            if (!in_array($filter, $acceptableStatuses, true)) {
                throw new BadRequestHttpException(sprintf("Unrecognised status '%s' in filters. Accepted values are: %s", $filter, join(', ', $acceptableStatuses)));
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
     * @param array $filterList
     *
     * @return array
     */
    public function buildUserFilters(array $filterList)
    {
        $userParams = [
            'creator' => 's_creator',
            'openskos:acceptedBy' => 's_acceptedBy',
            /* The following fields are copied from OpenSkos 2.2, but do not seem to work in solr! */
            'openskos:modifiedBy' => 's_contributor',
            'openskos:deletedBy' => 's_deletedBy',
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
     * @param array $filterList
     *
     * @return array
     */
    public function buildInteractionsFilters(array $filterList)
    {
        $dateParams = ['dateSubmitted', 'modified', 'dateAccepted', 'openskos:deleted'];

        $userParams = ['creator', 'openskos:modifiedBy', 'openskos:acceptedBy', 'openskos:deletedBy'];

        $parser = new ParserText();

        $dateParams = [
            'dateSubmitted' => 'd_dateSubmitted',
            'modified' => 'd_modified',
            'dateAccepted' => 'd_dateAccepted',
            'openskos:deleted' => 'd_dateDeleted',
        ];

        $dataOut = [];

        $nIdx = 0;
        $filtersAsStrings = [];

        foreach ($dateParams as $param => $solrField) {
            if (isset($filterList[$param])) {
                $dateQuery = $parser->buildDatePeriodQuery(
                    $solrField,
                    $filterList[$param]['from'] ?? null,
                    $filterList[$param]['until'] ?? null,
            );
                $filtersAsStrings[] = $dateQuery;
            }
        }

        if (count($filtersAsStrings)) {
            $dataOut['interactionsFilter'] = sprintf('( %s )', join(' OR ', $filtersAsStrings));
        }

        return $dataOut;
    }

    /**
     * @param int   $profile_id
     * @param array $to_apply
     *
     * @return array
     */
    public function retrieveSearchProfile(int $profile_id, array $to_apply)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('searchOptions')
            ->from('search_profiles')
            ->where('id = :id')
            ->setParameter('id', $profile_id);

        $filters = [];

        $res = $qb->execute();

        if ($res instanceof Statement) {
            $profile = $res->fetchAll();
            if (0 === count($profile)) {
                throw new BadRequestHttpException('The searchProfile id does not exist');
            }
            $searchOptions = unserialize($profile[0]['searchOptions']);

            if (isset($to_apply[self::ENTITY_INSTITUTION]) && true === $to_apply[self::ENTITY_INSTITUTION]) {
                if (isset($searchOptions['tenants']) && 0 !== count($searchOptions['tenants'])) {
                    $read_filters = $this->buildInstitutionFilters($searchOptions['tenants']);
                    $filters = array_merge($filters, $read_filters);
                }
            }
            if (isset($to_apply[self::ENTITY_SET]) && true === $to_apply[self::ENTITY_SET]) {
                if (isset($searchOptions['collections']) && 0 !== count($searchOptions['collections'])) {
                    $read_filters = $this->buildSetFilters($searchOptions['collections']);
                    $filters = array_merge($filters, $read_filters);
                }
            }
            if (isset($to_apply[self::ENTITY_CONCEPTSCHEME]) && true === $to_apply[self::ENTITY_CONCEPTSCHEME]) {
                if (isset($searchOptions['conceptScheme']) && 0 !== count($searchOptions['conceptScheme'])) {
                    $read_filters = $this->buildConceptSchemeFilters($searchOptions['conceptScheme']);
                    $filters = array_merge($filters, $read_filters);
                }
            }
            if (isset($to_apply[self::VALUE_STATUS]) && true === $to_apply[self::VALUE_STATUS]) {
                if (isset($searchOptions['status']) && 0 !== count($searchOptions['status'])) {
                    $read_filters = $this->buildStatusesFilters($searchOptions['status']);
                    $filters = array_merge($filters, $read_filters);
                }
            }
        }

        return $filters;
    }
}
