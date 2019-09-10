<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept\Controller;

use App\Helper\xsdDateHelper;
use App\OpenSkos\Concept\Solr\SolrJenaConceptRepository;
use App\OpenSkos\Filters\SolrFilterProcessor;
use App\Ontology\OpenSkos;
use App\OpenSkos\Concept\ConceptRepository;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Label\LabelRepository;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class Concept
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @param ApiRequest $apiRequest
     * @param string     $key
     *
     * @return array
     */
    private function processFilterFromRequest(
        ApiRequest $apiRequest,
        string $key
    ): array {
        /* Concept Schemes */
        $filter = [];
        $param = $apiRequest->getParameter($key);
        if (isset($param)) {
            $filter = preg_split('/\s*,\s*/', $param, -1, PREG_SPLIT_NO_EMPTY);
        }

        return $filter;
    }

    /**
     * @param ApiRequest $apiRequest
     * @param string     $key
     *
     * @return array
     */
    private function processDateStampsFromRequest(
        ApiRequest $apiRequest,
        string $key
    ): array {
        $datesOut = [];
        $xsdDateHelper = new xsdDateHelper();

        $dateParams = ['dateSubmitted', 'modified', 'dateAccepted', 'openskos:deleted'];

        foreach ($dateParams as $key) {
            $param = $apiRequest->getParameter($key);
            if (isset($param)) {
                $dates = preg_split('/\s*,\s*/', $param, -1, PREG_SPLIT_NO_EMPTY);
                if (count($dates) > 0) {
                    $rowOut = [];
                    $date1 = $dates[0];
                    if (!$xsdDateHelper->isValidXsdDateTime($date1)) {
                        throw new BadRequestHttpException('Dates must be a valid xsd:DateTime or xsdDuration');
                    } else {
                        $rowOut['from'] = $date1;
                    }
                    if (isset($dates[1])) {
                        $date2 = $dates[1];
                        if (!$xsdDateHelper->isValidXsdDateTime($date2)) {
                            throw new BadRequestHttpException('Dates must be a valid xsd:DateTime or xsdDuration');
                        } else {
                            $rowOut['until'] = $date2;
                        }
                    }
                    $datesOut[$key] = $rowOut;
                }
            }
        }

        return $datesOut;
    }

    /**
     * @param ApiRequest          $apiRequest
     * @param ConceptRepository   $repository
     * @param SolrFilterProcessor $solrFilterProcessor
     *
     * @return array
     */
    private function buildConceptFilters(
        ApiRequest $apiRequest,
        ConceptRepository $repository,
        SolrFilterProcessor $solrFilterProcessor
    ): array {
        /* From Spec
            searchProfile=id of a search profile. Stored in MySQL table 'search_profiles'.
        */

        /*
            No specification of this made available from Meertens. And not in Solr
            collections=comma separated list of collection URIs or IDs [On Hold: Predicate not known]
        */

        /* Institutions (tenants) */
        $param_institutions = $apiRequest->getInstitutions();
        $institutions_filter = $solrFilterProcessor->buildInstitutionFilters($param_institutions);

        /* Sets */
        $param_sets = $apiRequest->getSets();
        $sets_filter = $solrFilterProcessor->buildSetFilters($param_sets);

        /* Concept Schemes */
        $param_conceptschemes = $this->processFilterFromRequest($apiRequest, 'conceptSchemes');
        $conceptSchemes_filter = $solrFilterProcessor->buildConceptSchemeFilters($param_conceptschemes);

        /* Concept Schemes */
        $param_statuses = $this->processFilterFromRequest($apiRequest, 'statuses');
        $statuses_filter = $solrFilterProcessor->buildStatusesFilters($param_statuses);

        /* Concept Schemes */
        $param_dates = $this->processDateStampsFromRequest($apiRequest, 'statuses');
        $interactions_filter = $solrFilterProcessor->buildInteractionsFilters($param_dates);

        $param_users = [];
        $param_users['creator'] = $this->processFilterFromRequest($apiRequest, 'creator');
        $param_users['openskos:modifiedBy'] = $this->processFilterFromRequest($apiRequest, 'openskos:modifiedBy');
        $param_users['openskos:acceptedBy'] = $this->processFilterFromRequest($apiRequest, 'openskos:acceptedBy');
        $param_users['openskos:deletedBy'] = $this->processFilterFromRequest($apiRequest, 'openskos:deletedBy');

        $users_filter = $solrFilterProcessor->buildUserFilters($param_users);

        $param_profile = $apiRequest->getSearchProfile();

        $full_filter = array_merge(
            $institutions_filter,
            $sets_filter,
            $conceptSchemes_filter,
            $statuses_filter,
            $interactions_filter,
            $users_filter
        );

        if ($param_profile) {
            if (0 !== count($full_filter)) {
                throw new BadRequestHttpException('Search profile filters cannot be combined with other filters (possible conflicts).');
            }
            $to_apply = [
                solrFilterProcessor::ENTITY_INSTITUTION => true,
                solrFilterProcessor::ENTITY_SET => true,
                solrFilterProcessor::ENTITY_CONCEPTSCHEME => true,
                solrFilterProcessor::VALUE_STATUS => true,
            ];
            $full_filter = $solrFilterProcessor->retrieveSearchProfile($param_profile, $to_apply);
        }

        return $full_filter;
    }

    private function buildSelectionParameters(
        ApiRequest $apiRequest,
        ConceptRepository $repository
    ): array {
        $selectionParameters = ['labels' => []];
        $sel = $apiRequest->getParameter('fields', '');

        if (isset($sel)) {
            $sel = preg_split('/\s*,\s*/', $sel, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (isset($sel) && is_iterable($sel)) {
            foreach ($sel as $param) {
                if (preg_match('/^(pref|alt|hidden|)(label)\(*(\w{0,3})\)*$/i', $param, $capture)) {
                    $label = sprintf('%sLabel', $capture[1]);
                    $lang = $capture[3];

                    $selectionParameters['labels'][$capture[0]] = ['type' => $label, 'lang' => $lang];
                } elseif ('notation' === $param) {
                    $selectionParameters['notation'] = ['type' => 'notation'];
                }
            }
        }

        $param = $apiRequest->getParameter('wholeword', '0');
        if (filter_var($param, FILTER_VALIDATE_BOOLEAN)) {
            $selectionParameters['wholeword'] = ['type' => 'wholeword'];
        }

        return $selectionParameters;
    }

    /**
     * @Route(path="/concepts", methods={"GET"})
     *
     * @param ApiRequest          $apiRequest
     * @param ConceptRepository   $repository
     * @param SolrFilterProcessor $solrFilterProcessor
     *
     * @return ListResponse
     */
    public function concepts(
        ApiRequest $apiRequest,
        ConceptRepository $repository,
        SolrFilterProcessor $solrFilterProcessor
    ): ListResponse {
        $full_filter = $this->buildConceptFilters($apiRequest, $repository, $solrFilterProcessor);

        $concepts = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter);

        return new ListResponse(
            $concepts,
            count($concepts),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/concept/{id}", methods={"GET"})
     *
     * @param InternalResourceId $id
     * @param ApiRequest         $apiRequest
     * @param ConceptRepository  $repository
     * @param LabelRepository    $labelRepository
     *
     * @return ScalarResponse
     */
    public function concept(
        InternalResourceId $id,
        ApiRequest $apiRequest,
        ConceptRepository $repository,
        LabelRepository $labelRepository
    ): ScalarResponse {
        $concept = $repository->findOneBy(
            new Iri(OpenSkos::UUID),
            $id
        );
        if (null === $concept) {
            throw new NotFoundHttpException("The concept $id could not be retreived.");
        }
        if (2 === $apiRequest->getLevel()) {
            $concept->loadFullXlLabels($labelRepository);
        }

        return new ScalarResponse($concept, $apiRequest->getFormat());
    }

    /**
     * Version for foreign Uri's. For now, this is a wrapper for the 'native uri' functionality, but that will probably change.
     *
     * @Route(path="/concept", methods={"GET"})
     *
     * @param ApiRequest        $apiRequest
     * @param ConceptRepository $repository
     * @param LabelRepository   $labelRepository
     *
     * @return ScalarResponse
     */
    public function conceptByForeignUri(
        ApiRequest $apiRequest,
        ConceptRepository $repository,
        LabelRepository $labelRepository
    ): ScalarResponse {
        $foreignUri = $apiRequest->getForeignUri();

        if (!isset($foreignUri)) {
            throw new BadRequestHttpException("Unable to determine URI for concept. Please either request a UUID in the path, or specifiy the 'uri' parameter");
        }
        if (!filter_var($foreignUri, FILTER_VALIDATE_URL)) {
            throw new BadRequestHttpException("'uri' parameter must be a URI.");
        }

        $concept = $repository->findByIri(
            new Iri($foreignUri)
        );
        if (null === $concept) {
            throw new NotFoundHttpException("The concept <$foreignUri> could not be retrieved.");
        }
        if (2 === $apiRequest->getLevel()) {
            $concept->loadFullXlLabels($labelRepository);
        }

        return new ScalarResponse($concept, $apiRequest->getFormat());
    }

    /**
     * @Route(path="/autocomplete", methods={"GET"})
     *
     * @param ApiRequest                $apiRequest
     * @param SolrJenaConceptRepository $repository
     * @param SolrFilterProcessor       $solrFilterProcessor
     *
     * @return ListResponse
     *
     * @throws \Exception
     */
    public function autocomplete(
        ApiRequest $apiRequest,
        SolrJenaConceptRepository $repository,
        SolrFilterProcessor $solrFilterProcessor
    ): ListResponse {
        $full_filter = $this->buildConceptFilters($apiRequest, $repository, $solrFilterProcessor);

        $full_selection = $this->buildSelectionParameters($apiRequest, $repository);
        //$full_selection = $this->buildProjectionParameters($apiRequest, $repository);

        $searchText = $apiRequest->getParameter('text', '*');

        //@todo projection params

        $concepts = $repository->fullSolrSearch($searchText, $apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter, $full_selection);

        return new ListResponse(
            $concepts,
            count($concepts),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }
}
