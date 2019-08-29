<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept\Controller;

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
            conceptSchemes=comma separated list of concept scheme URIs or IDs. Applied to http://www.w3.org/2004/02/skos/core#inScheme
            collections=comma separated list of collection URIs or IDs [On Hold: Predicate not known]
            searchProfile=id of a search profile. Stored in MySQL table 'search_profiles'.
            dateSubmitted=xsd:duration and some shortcuts (?). Applied to http://purl.org/dc/terms/dateSubmitted
            modified=xsd:duration and some shortcuts (?). Applied to http://purl.org/dc/terms/modified
            dateAccepted=xsd:duration and some shortcuts (?). Applied to http://purl.org/dc/terms/dateAccepted
            openskos:deleted=xsd:duration and some shortcuts (?). Applied to http://openskos.org/xmlns#dateDeleted
            statuses=comma separated list of statuses. Applied to http://openskos.org/xmlns#status
            candidate
            approved
            redirected
            not_compliant
            rejected
            obsolete
            deleted
            creator=comma separated list of user URIs or IDs. Applied to http://purl.org/dc/terms/creator
            openskos:modifiedBy=comma separated list of user URIs or IDs. Applied to http://openskos.org/xmlns#modifiedBy
            openskos:acceptedBy=comma separated list of user URIs or IDs. Applied to http://openskos.org/xmlns#acceptedBy
            openskos:deletedBy=comma separated list of user URIs or IDs. Applied to http://openskos.org/xmlns#deletedBy
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

        $param_profile = $apiRequest->getSearchProfile();

        $full_filter = array_merge(
            $institutions_filter,
            $sets_filter,
            $conceptSchemes_filter
        );

        if ($param_profile) {
            if (0 !== count($full_filter)) {
                throw new BadRequestHttpException('Search profile filters cannot be combined with other filters (possible conflicts).');
            }
            $to_apply = [solrFilterProcessor::ENTITY_INSTITUTION => true, solrFilterProcessor::ENTITY_SET => true];
            $full_filter = $solrFilterProcessor->retrieveSearchProfile($param_profile, $to_apply);
        }

        return $full_filter;
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
}
