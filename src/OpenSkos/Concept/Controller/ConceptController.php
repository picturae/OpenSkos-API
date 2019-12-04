<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept\Controller;

use App\Annotation\Error;
use App\Annotation\OA;
use App\Entity\User;
use App\Exception\ApiException;
use App\Helper\xsdDateHelper;
use App\Ontology\OpenSkos;
use App\Ontology\Skos;
use App\OpenSkos\ApiFilter;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Concept\Concept;
use App\OpenSkos\Concept\Concept as SkosConcept;
use App\OpenSkos\Concept\ConceptRepository;
use App\OpenSkos\ConceptScheme\ConceptSchemeRepository;
use App\OpenSkos\DataLevels\Level2Processor;
use App\OpenSkos\Filters\SolrFilterProcessor;
use App\OpenSkos\Institution\InstitutionRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Label\LabelRepository;
use App\OpenSkos\Set\SetRepository;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ConceptController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Concept constructor.
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * Processes comma separated parameters for filters.
     */
    private function processFilterFromRequest(
        ApiRequest $apiRequest,
        string $key
    ): array {
        /* Concept Schemes */
        $filter = [];
        $param  = $apiRequest->getParameter($key);
        if (isset($param)) {
            $filter = preg_split('/\s*,\s*/', $param, -1, PREG_SPLIT_NO_EMPTY);
        }

        return $filter;
    }

    /**
     * Extracts datestamp from request strings. Only a restricted number of formats are accepted
     * https://github.com/picturae/API/blob/develop/doc/OpenSKOS-API.md#concepts.
     */
    private function processDateStampsFromRequest(
        ApiRequest $apiRequest,
        string $key
    ): array {
        $datesOut      = [];
        $xsdDateHelper = new xsdDateHelper();

        $dateParams = ['dateSubmitted', 'modified', 'dateAccepted', 'openskos:deleted'];

        foreach ($dateParams as $key) {
            $param = $apiRequest->getParameter($key);
            if (isset($param)) {
                $dates = preg_split('/\s*,\s*/', $param, -1, PREG_SPLIT_NO_EMPTY);
                if (count($dates) > 0) {
                    $rowOut = [];
                    $date1  = $dates[0];
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
     * Builds the filters for a concept. Should follow
     * https://github.com/picturae/API/blob/develop/doc/OpenSKOS-API.md#concepts.
     *
     * @param ConceptRepository $repository
     */
    private function buildConceptFilters(
        ApiRequest $apiRequest,
        ApiFilter $apiFilter,
        SolrFilterProcessor $solrFilterProcessor
    ): array {
        // TODO: Don't use non-default filters anymore
        $apiFilter->addFilter('openskos:tenant', $apiRequest->getInstitutions());
        $apiFilter->addFilter('openskos:set', $apiRequest->getSets());
        $apiFilter->addFilter('skos:ConceptScheme', $this->processFilterFromRequest($apiRequest, 'conceptSchemes'));
        $apiFilter->addFilter('openskos:status', $this->processFilterFromRequest($apiRequest, 'statuses'));
        $apiFilter->addFilter('dcterms:creator:', $this->processFilterFromRequest($apiRequest, 'creator'));
        $apiFilter->addFilter('openskos:modifiedBy:', $this->processFilterFromRequest($apiRequest, 'openskos:modifiedBy'));
        $apiFilter->addFilter('openskos:acceptedBy:', $this->processFilterFromRequest($apiRequest, 'openskos:acceptedBy'));
        $apiFilter->addFilter('openskos:deletedBy:', $this->processFilterFromRequest($apiRequest, 'openskos:deletedBy'));
        $full_filter = $apiFilter->buildFilters('solr');

        /*
            No specification of this made available from Meertens. And not in Solr
            collections=comma separated list of collection URIs or IDs [On Hold: Predicate not known]
        */

        /* /1* Concept Schemes *1/ */
        /* $param_dates = $this->processDateStampsFromRequest($apiRequest, 'statuses'); */
        /* $interactions_filter = $solrFilterProcessor->buildInteractionsFilters($param_dates); */

        return $full_filter;
    }

    /**
     * Builds the selection parameters for a concept. Should follow
     * https://github.com/picturae/API/blob/develop/doc/OpenSKOS-API.md#concepts.
     */
    private function buildSelectionParameters(
        ApiRequest $apiRequest,
        ConceptRepository $repository
    ): array {
        $selectionParameters = ['labels' => []];
        $sel                 = $apiRequest->getParameter('fields', '');

        if (isset($sel)) {
            $sel = preg_split('/\s*,\s*/', $sel, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (isset($sel) && is_iterable($sel)) {
            foreach ($sel as $param) {
                if (preg_match('/^(pref|alt|hidden|)(label)\(*(\w{0,3})\)*$/i', $param, $capture)) {
                    $label = sprintf('%sLabel', $capture[1]);
                    $lang  = $capture[3];

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
     * Builds the projection parameters for a concept. Should follow
     * https://github.com/picturae/API/blob/develop/doc/OpenSKOS-API.md#concepts.
     */
    private function buildProjectionParameters(
        ApiRequest $apiRequest,
        ConceptRepository $repository
    ): array {
        /* Levels are dealt with elsewhere; they are applicable to more that just concepts */

        $projectionParameters = [];
        $props                = $apiRequest->getParameter('props', '');

        if (isset($props)) {
            $props = preg_split('/\s*,\s*/', $props, -1, PREG_SPLIT_NO_EMPTY);
        }

        $acceptable_fields  = SkosConcept::getAcceptableFields();
        $language_sensitive = SkosConcept::getLanguageSensitive();
        $meta_groups        = SkosConcept::getMetaGroups();
        if (isset($props) && is_iterable($props)) {
            foreach ($props as $param) {
                //The language flag might be buried in brackets
                if (preg_match('/^([a-zA-Z:]*)\(*?(\w{0,3}?)\)*?$/i', $param, $capture)) {
                    $field = $capture[1];
                    $lang  = $capture[2];
                    if (in_array($field, $language_sensitive, true) && '' !== $lang) {
                        //@Todo: Not going work
                        $projectionParameters[$field] = ['lang' => $lang];
                    } elseif ('' !== $lang) {
                        throw new BadRequestHttpException(sprintf("No language support for field '%s'", $field));
                    } elseif (isset($acceptable_fields[$field])) { //The spec doesn't mention if these keys are case-sensitive, so lets just assume they are
                        $projectionParameters[$field] = ['lang' => ''];
                    } elseif (isset($meta_groups[$field])) {
                        $projectionParameters = $meta_groups[$field];
                    } else {
                        throw new BadRequestHttpException(sprintf("Field '%s' is not supported for projection", $field));
                    }
                }
            }
        } else {
            $projectionParameters = $meta_groups['default'];
        }

        //If we have chosen a projection parameter with an XL label, add that to our list too.
        $labelMappings = SkosConcept::getAcceptableFieldsToXl();
        foreach ($labelMappings  as $skosLabel => $skosXLLabel) {
            if (isset($projectionParameters[$skosLabel])) {
                $projectionParameters[$skosXLLabel] = $projectionParameters[$skosLabel];
            }
        }

        //Whatever else happens, the Rdf::Type is projected, to ensure there's at least one triple
        if (count($projectionParameters) > 0) {
            $projectionParameters['type'] = ['lang' => ''];
        }

        return $projectionParameters;
    }

    /**
     * @Route(path="/concept/{id}.{format?}", methods={"GET"})
     */
    public function getConcept(
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
     * @Route(path="/concept.{format?}", methods={"GET"})
     */
    public function getConceptByForeignUri(
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
     * @Route(path="/concepts.{format?}", methods={"GET"})
     *
     * @throws Exception
     */
    public function getAllConcepts(
        ApiRequest $apiRequest,
        ApiFilter $apiFilter,
        ConceptRepository $repository,
        SolrFilterProcessor $solrFilterProcessor,
        LabelRepository $labelRepository
    ): ListResponse {
        $full_filter = $this->buildConceptFilters($apiRequest, $apiFilter, $solrFilterProcessor);

        $full_projection = $this->buildProjectionParameters($apiRequest, $repository);

        $concepts = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter, $full_projection);

        if (2 === $apiRequest->getLevel()) {
            $levelProcessor = new Level2Processor();
            $levelProcessor->AddLevel2Data($labelRepository, $concepts);
        }

        return new ListResponse(
            $concepts,
            count($concepts),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/concepts.{format?}", methods={"POST"})
     *
     * @OA\Summary("Create one or more new concepts")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     *   @OA\Schema\ObjectLiteral(name="@context",in="body"),
     *   @OA\Schema\ArrayLiteral(
     *     name="@graph",
     *     in="body",
     *     items=@OA\Schema\ObjectLiteral(class=SkosConcept::class),
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\JsonRdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=SkosConcept::class),
     *     ),
     *   }),
     * )
     *
     * @Error(code="concept-create-empty-or-corrupt-body",
     *        status=400,
     *        description="The body passed to this endpoint was either missing or corrupt"
     * )
     * @Error(code="concept-create-already-exists",
     *        status=409,
     *        description="A Concept with the given iri already exists",
     *        fields={"iri"}
     * )
     * @Error(code="concept-create-tenant-does-not-exist",
     *        status=400,
     *        description="The given tenant to create a Concept for does not exist",
     *        fields={"tenant"}
     * )
     * @Error(code="concept-create-set-does-not-exist",
     *        status=400,
     *        description="The given set to create a Concept for does not exist",
     *        fields={"set"}
     * )
     * @Error(code="concept-create-conceptscheme-does-not-exist",
     *        status=400,
     *        description="The given conceptscheme to create a Concept for does not exist",
     *        fields={"conceptscheme"}
     * )
     *
     * @throws Exception
     */
    public function postConcept(
        ApiRequest $apiRequest,
        ApiFilter $apiFilter,
        SolrFilterProcessor $solrFilterProcessor,
        LabelRepository $labelRepository,
        ConceptRepository $conceptRepository,
        ConceptSchemeRepository $conceptSchemeRepository,
        SetRepository $setRepository,
        InstitutionRepository $institutionRepository
    ): ListResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();

        // Load data into sets
        $graph    = $apiRequest->getGraph();
        $concepts = $conceptRepository->fromGraph($graph);
        if (is_null($concepts)||(!count($concepts))) {
            throw new ApiException('concept-create-empty-or-corrupt-body');
        }

        // Check if the resources already exist
        foreach ($concepts as $concept) {
            if ($concept->exists()) {
                throw new ApiException('concept-create-already-exists', [
                    'iri' => $concept->iri()->getUri(),
                ]);
            }
        }

        // Ensure the tenants exist
        foreach ($concepts as $concept) {
            $tenantCode = $concept->getValue(OpenSkos::TENANT)->value();
            $tenant     = $institutionRepository->findOneBy(
                new Iri(OpenSkos::CODE),
                new InternalResourceId($tenantCode)
            );
            if (is_null($tenant)) {
                throw new ApiException('concept-create-tenant-does-not-exist', [
                    'tenant' => $tenantCode,
                ]);
            }
        }

        // Ensure the sets exist
        foreach ($concepts as $concept) {
            $setIri = $concept->getValue(OpenSkos::SET)->getUri();
            $set    = $setRepository->findByIri(new Iri($setIri));
            if (is_null($set)) {
                throw new ApiException('concept-create-set-does-not-exist', [
                    'set' => $setIri,
                ]);
            }
        }

        // Ensure the conceptschemes exist
        foreach ($concepts as $concept) {
            $conceptSchemes = $concept->getProperty(Skos::IN_SCHEME);
            foreach ($conceptSchemes as $conceptSchemeIri) {
                $conceptScheme = $conceptSchemeRepository->findByIri($conceptSchemeIri);
                if (is_null($conceptScheme)) {
                    throw new ApiException('concept-create-conceptscheme-does-not-exist', [
                        'conceptscheme' => $conceptSchemeIri->getUri(),
                    ]);
                }
            }
        }

        // Save all given conceptSchemes
        foreach ($concepts as $concept) {
            $errors = $concept->save();
            if ($errors) {
                throw new ApiException($errors[0]);
            }
        }

        return new ListResponse(
            $concepts,
            count($concepts),
            0,
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/concepts.{format?}", methods={"PUT"})
     *
     * @Error(code="concept-update-empty-or-corrupt-body",
     *        status=400,
     *        description="The body passed to this endpoint was either missing or corrupt"
     * )
     * @Error(code="concept-update-does-not-exist",
     *        status=400,
     *        description="The set with the given iri does not exist",
     *        fields={"iri"}
     * )
     * @Error(code="concept-update-tenant-does-not-exist",
     *        status=400,
     *        description="The given tenant to update a Concept for does not exist",
     *        fields={"tenant"}
     * )
     * @Error(code="concept-update-set-does-not-exist",
     *        status=400,
     *        description="The given set to update a Concept for does not exist",
     *        fields={"set"}
     * )
     * @Error(code="concept-update-concept-does-not-exist",
     *        status=400,
     *        description="The given conceptscheme to create a Concept for does not exist",
     *        fields={"conceptscheme"}
     * )
     */
    public function putConcept(
        ApiRequest $apiRequest,
        ConceptRepository $conceptRepository,
        ConceptSchemeRepository $conceptSchemeRepository,
        SetRepository $setRepository,
        InstitutionRepository $institutionRepository
    ): ListResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();
        /** @var User $user */
        $user = $auth->getUser();

        // Load data into concept schemes
        $graph     = $apiRequest->getGraph();
        $concepts  = $conceptRepository->fromGraph($graph);
        if (is_null($concepts)||(!count($concepts))) {
            throw new ApiException('concept-update-empty-or-corrupt-body');
        }

        // Validate all given resources
        $errors = [];
        foreach ($concepts as $concept) {
            if (!$concept->exists()) {
                throw new ApiException('concept-update-does-not-exist', [
                    'iri' => $concept->iri()->getUri(),
                ]);
            }
            $errors = array_merge($errors, $concept->errors());
        }
        if (count($errors)) {
            foreach ($errors as $error) {
                throw new ApiException($error);
            }
        }

        // Ensure the tenants exist
        foreach ($concepts as $concept) {
            $tenantCode = $concept->getValue(OpenSkos::TENANT)->value();
            $tenant     = $institutionRepository->findOneBy(
                new Iri(OpenSkos::CODE),
                new InternalResourceId($tenantCode)
            );
            if (is_null($tenant)) {
                throw new ApiException('conceptscheme-update-tenant-does-not-exist', [
                    'tenant' => $tenantCode,
                ]);
            }
        }

        // Ensure the sets exist
        foreach ($concepts as $concept) {
            $setIri = $concept->getValue(OpenSkos::SET)->getUri();
            $set    = $setRepository->findByIri(new Iri($setIri));
            if (is_null($set)) {
                throw new ApiException('concept-update-set-does-not-exist', [
                    'set' => $setIri,
                ]);
            }
        }

        // Ensure the concepts exist
        foreach ($concepts as $concept) {
            $conceptSchemes = $concept->getProperty(Skos::IN_SCHEME);
            foreach ($conceptSchemes as $conceptSchemeIri) {
                $conceptScheme = $conceptSchemeRepository->findByIri($conceptSchemeIri);
                if (is_null($conceptScheme)) {
                    throw new ApiException('concept-update-conceptscheme-does-not-exist', [
                        'conceptscheme' => $conceptSchemeIri->getUri(),
                    ]);
                }
            }
        }

        // Rebuild all given Concepts
        $modifier = new Iri(OpenSkos::MODIFIED_BY);
        foreach ($concepts as $concept) {
            $concept->setValue($modifier, $user->iri());
            $errors = $concept->update();
            if ($errors) {
                throw new ApiException($errors[0]);
            }
        }

        return new ListResponse(
            $concepts,
            count($concepts),
            0,
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/autocomplete.{format?}", methods={"GET"})
     *
     * @throws Exception
     */
    public function autocomplete(
        ApiRequest $apiRequest,
        ApiFilter $apiFilter,
        ConceptRepository $repository,
        SolrFilterProcessor $solrFilterProcessor
    ): ListResponse {
        $full_filter = $this->buildConceptFilters($apiRequest, $apiFilter, $solrFilterProcessor);

        $full_selection  = $this->buildSelectionParameters($apiRequest, $repository);
        $full_projection = $this->buildProjectionParameters($apiRequest, $repository);

        $searchText = $apiRequest->getParameter('text', '*');

        $concepts = $repository->fullSolrSearch($searchText, $apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter, $full_selection, $full_projection);

        return new ListResponse(
            $concepts,
            count($concepts),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }
}
