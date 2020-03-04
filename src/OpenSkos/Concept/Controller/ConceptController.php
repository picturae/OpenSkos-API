<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept\Controller;

use App\Annotation\Error;
use App\Annotation\ErrorInherit;
use App\Annotation\OA;
use App\EasyRdf\TripleFactory;
use App\Entity\User;
use App\Exception\ApiException;
use App\Helper\xsdDateHelper;
use App\Ontology\OpenSkos;
use App\Ontology\Skos;
use App\OpenSkos\ApiFilter;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Concept\Concept;
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
use App\Security\Authentication;
use Exception;
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
     *
     * @ErrorInherit(class=ApiRequest::class, method="getParameter")
     */
    private function processFilterFromRequest(
        ApiRequest $apiRequest,
        string $key
    ): array {
        /* Concept Schemes */
        $filter = [];
        $param  = $apiRequest->getParameter($key);
        if (isset($param)) {
            $filter = array_filter(str_getcsv($param));
        }

        return $filter;
    }

    /**
     * Extracts datestamp from request strings. Only a restricted number of formats are accepted
     * https://github.com/picturae/API/blob/develop/doc/OpenSKOS-API.md#concepts.
     *
     * @Error(code="conceptcontroller-process-date-stamps-from-request-invalid-date-type",
     *        status=400,
     *        description="Dates must be a valid xsd:DateTime or xsdDuration"
     * )
     *
     * @ErrorInherit(class=ApiRequest::class   , method="getParameter"      )
     * @ErrorInherit(class=xsdDateHelper::class, method="__construct"       )
     * @ErrorInherit(class=xsdDateHelper::class, method="isValidXsdDateTime")
     */
    private function processDateStampsFromRequest(
        ApiRequest $apiRequest
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
                        throw new ApiException('conceptcontroller-process-date-stamps-from-request-invalid-date-type');
                    } else {
                        $rowOut['from'] = $date1;
                    }
                    if (isset($dates[1])) {
                        $date2 = $dates[1];
                        if (!$xsdDateHelper->isValidXsdDateTime($date2)) {
                            throw new ApiException('conceptcontroller-process-date-stamps-from-request-invalid-date-type');
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
     *
     * @ErrorInherit(class=ApiFilter::class        , method="buildFilters"            )
     * @ErrorInherit(class=ApiRequest::class       , method="getInstitutions"         )
     * @ErrorInherit(class=ApiRequest::class       , method="getSets"                 )
     * @ErrorInherit(class=ConceptController::class, method="processFilterFromRequest")
     */
    private function buildConceptFilters(
        ApiRequest $apiRequest,
        ApiFilter $apiFilter,
        SolrFilterProcessor $solrFilterProcessor
    ): array {
        // Map some filters the ApiFilter class doesn't do by default but were in the spec
        $apiFilter->addFilter('openskos:tenant', $apiRequest->getInstitutions());
        $apiFilter->addFilter('openskos:set', $apiRequest->getSets());
        $apiFilter->addFilter('skos:inScheme', $this->processFilterFromRequest($apiRequest, 'conceptSchemes'));
        $apiFilter->addFilter('openskos:status', $this->processFilterFromRequest($apiRequest, 'statuses'));

        // TODO: Extra solr-mapping in ApiFilter is required for these
        /* $apiFilter->addFilter('dcterms:creator', $this->processFilterFromRequest($apiRequest, 'creator')); */
        /* $apiFilter->addFilter('openskos:modifiedBy', $this->processFilterFromRequest($apiRequest, 'openskos:modifiedBy')); */
        /* $apiFilter->addFilter('openskos:acceptedBy', $this->processFilterFromRequest($apiRequest, 'openskos:acceptedBy')); */
        /* $apiFilter->addFilter('openskos:deletedBy', $this->processFilterFromRequest($apiRequest, 'openskos:deletedBy')); */
        /* $apiFilter->addFilter('openskos:modifiedBy', $this->processFilterFromRequest($apiRequest, 'modifiedBy')); */
        /* $apiFilter->addFilter('openskos:acceptedBy', $this->processFilterFromRequest($apiRequest, 'acceptedBy')); */
        /* $apiFilter->addFilter('openskos:deletedBy', $this->processFilterFromRequest($apiRequest, 'deletedBy')); */

        $prefabFilters = $apiFilter->buildFilters('solr');

        /*
            No specification of this made available from Meertens. And not in Solr
            collections=comma separated list of collection URIs or IDs [On Hold: Predicate not known]
        */

        /* Concept Schemes */
        $param_dates         = $this->processDateStampsFromRequest($apiRequest);
        $interactions_filter = $solrFilterProcessor->buildInteractionsFilters($param_dates);

        /* User filters */
        $param_users                        = [];
        $param_users['creator']             = $this->processFilterFromRequest($apiRequest, 'creator');
        $param_users['openskos:modifiedBy'] = $this->processFilterFromRequest($apiRequest, 'openskos:modifiedBy');
        $param_users['openskos:acceptedBy'] = $this->processFilterFromRequest($apiRequest, 'openskos:acceptedBy');
        $param_users['openskos:deletedBy']  = $this->processFilterFromRequest($apiRequest, 'openskos:deletedBy');

        $users_filter = $solrFilterProcessor->buildUserFilters($param_users);

        /* Merge all filters */
        $full_filter = array_merge(
            $prefabFilters,
            $interactions_filter,
            $users_filter
        );

        return $full_filter;
    }

    /**
     * Builds the selection parameters for a concept. Should follow
     * https://github.com/picturae/API/blob/develop/doc/OpenSKOS-API.md#concepts.
     *
     * @ErrorInherit(class=ApiRequest::class, method="getParameter")
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
                if (preg_match('/^(label)\(*(\w{0,3})\)*$/i', $param, $capture)) {
                    $lang                                       = $capture[2];
                    $selectionParameters['labels'][$capture[0]] = ['type' => 'LexicalLabels', 'lang' => $lang];
                } elseif (preg_match('/^(pref|alt|hidden)(label)\(*(\w{0,3})\)*$/i', $param, $capture)) {
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
     *
     * @Error(code="conceptcontroller-build-projection-parameters-field-no-language-support",
     *        status=400,
     *        description="Field has no language support",
     *        fields={"field"}
     * )
     * @Error(code="conceptcontroller-build-projection-parameters-field-no-projection-support",
     *        status=400,
     *        description="Field has no projection support",
     *        fields={"field"}
     * )
     *
     * @ErrorInherit(class=ApiRequest::class, method="getParameter"           )
     * @ErrorInherit(class=Concept::class   , method="getAcceptableFieldsToXl")
     * @ErrorInherit(class=Concept::class   , method="getAcceptableFields"    )
     * @ErrorInherit(class=Concept::class   , method="getLanguageSensitive"   )
     * @ErrorInherit(class=Concept::class   , method="getMetaGroups"          )
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

        $acceptable_fields  = Concept::getAcceptableFields();
        $language_sensitive = Concept::getLanguageSensitive();
        $meta_groups        = Concept::getMetaGroups();
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
                        throw new ApiException('conceptcontroller-build-projection-parameters-field-no-language-support', [
                            'field' => $field,
                        ]);
                    } elseif (isset($acceptable_fields[$field])) { //The spec doesn't mention if these keys are case-sensitive, so lets just assume they are
                        $projectionParameters[$field] = ['lang' => ''];
                    } elseif (isset($meta_groups[$field])) {
                        $projectionParameters = $meta_groups[$field];
                    } else {
                        throw new ApiException('conceptcontroller-build-projection-parameters-field-no-projection-support', [
                            'field' => $field,
                        ]);
                    }
                }
            }
        } else {
            $projectionParameters = $meta_groups['default'];
        }

        //If we have chosen a projection parameter with an XL label, add that to our list too.
        $labelMappings = Concept::getAcceptableFieldsToXl();
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
     *
     * @OA\Summary("Retreive a concept using it's identifier")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="id",
     *     in="path",
     *     example="1911",
     *   ),
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Concept::class),
     *     ),
     *   }),
     * )
     *
     * @Error(code="concept-getone-not-found",
     *        status=404,
     *        description="The requested concept could not be retreived",
     *        fields={"uuid"}
     * )
     *
     * @ErrorInherit(class=ApiRequest::class        , method="__construct"     )
     * @ErrorInherit(class=ApiRequest::class        , method="getFormat"       )
     * @ErrorInherit(class=ApiRequest::class        , method="getLevel"        )
     * @ErrorInherit(class=Concept::class           , method="loadFullXlLabels")
     * @ErrorInherit(class=ConceptRepository::class , method="__construct"     )
     * @ErrorInherit(class=ConceptRepository::class , method="findOneBy"       )
     * @ErrorInherit(class=InternalResourceId::class, method="__construct"     )
     * @ErrorInherit(class=InternalResourceId::class, method="id"              )
     * @ErrorInherit(class=Iri::class               , method="__construct"     )
     * @ErrorInherit(class=ScalarResponse::class    , method="__construct"     )
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
            throw new ApiException('concept-getone-not-found', [
                'uuid' => $id->id(),
            ]);
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
     *
     * @OA\Summary("Retreive a concept by foreign URI")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     *   @OA\Schema\StringLiteral(
     *     name="uri",
     *     in="query",
     *     example="http://openskos.org/pic/1911",
     *     required=true,
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Concept::class),
     *     ),
     *   }),
     * )
     *
     * @Error(code="concept-getonebyfuri-param-uri-missing",
     *        status=400,
     *        description="Unable to determine URI for concept. Please either request a UUID in the path, or specifiy the 'uri' parameter"
     * )
     * @Error(code="concept-getonebyfuri-param-uri-invalid",
     *        status=400,
     *        description="'uri' parameter must be a URI.",
     *        fields={"foreignUri"}
     * )
     * @Error(code="concept-getonebyfuri-not-found",
     *        status=400,
     *        description="The requested concept could not be retrieved.",
     *        fields={"foreignUri"}
     * )
     *
     * @ErrorInherit(class=ApiRequest::class       , method="__construct"     )
     * @ErrorInherit(class=ApiRequest::class       , method="getForeignUri"   )
     * @ErrorInherit(class=ApiRequest::class       , method="getFormat"       )
     * @ErrorInherit(class=ApiRequest::class       , method="getLevel"        )
     * @ErrorInherit(class=Concept::class          , method="loadFullXlLabels")
     * @ErrorInherit(class=ConceptRepository::class, method="__construct"     )
     * @ErrorInherit(class=ConceptRepository::class, method="findByIri"       )
     * @ErrorInherit(class=ScalarResponse::class   , method="__construct"     )
     */
    public function getConceptByForeignUri(
        ApiRequest $apiRequest,
        ConceptRepository $repository,
        LabelRepository $labelRepository
    ): ScalarResponse {
        $foreignUri = $apiRequest->getForeignUri();

        if (!isset($foreignUri)) {
            throw new ApiException('concept-getonebyfuri-param-uri-missing');
        }
        if (!filter_var($foreignUri, FILTER_VALIDATE_URL)) {
            throw new ApiException('concept-getonybyfuri-param-uri-invalid', [
                'foreignUri' => $foreignUri,
            ]);
        }

        $concept = $repository->findByIri(
            new Iri($foreignUri)
        );
        if (null === $concept) {
            throw new ApiException('concept-getonybyfuri-not-found', [
                'foreignUri' => $foreignUri,
            ]);
        }
        if (2 === $apiRequest->getLevel()) {
            $concept->loadFullXlLabels($labelRepository);
        }

        return new ScalarResponse($concept, $apiRequest->getFormat());
    }

    /**
     * @Route(path="/concepts.{format?}", methods={"GET"})
     *
     * @OA\Summary("Retreive all (filtered) concepts")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Concept::class),
     *     ),
     *   }),
     * )
     *
     * @ErrorInherit(class=ApiRequest::class         , method="__construct"              )
     * @ErrorInherit(class=ApiRequest::class         , method="getFormat"                )
     * @ErrorInherit(class=ApiRequest::class         , method="getLevel"                 )
     * @ErrorInherit(class=ApiRequest::class         , method="getLimit"                 )
     * @ErrorInherit(class=ApiRequest::class         , method="getOffset"                )
     * @ErrorInherit(class=ApiFilter::class          , method="__construct"              )
     * @ErrorInherit(class=ConceptController::class  , method="buildConceptFilters"      )
     * @ErrorInherit(class=ConceptController::class  , method="buildProjectionParameters")
     * @ErrorInherit(class=ConceptRepository::class  , method="__construct"              )
     * @ErrorInherit(class=ConceptRepository::class  , method="all"                      )
     * @ErrorInherit(class=Level2Processor::class    , method="AddLevel2Data"            )
     * @ErrorInherit(class=ListResponse::class       , method="__construct"              )
     * @ErrorInherit(class=SolrFilterProcessor::class, method="__construct"              )
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

        $full_selection  = $this->buildSelectionParameters($apiRequest, $repository);
        $full_projection = $this->buildProjectionParameters($apiRequest, $repository);

        $searchText = $apiRequest->getParameter('text', '*');

        $concepts = $repository->fullSolrSearch($searchText, $apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter, $full_selection, $full_projection);

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
     *     items=@OA\Schema\ObjectLiteral(class=Concept::class),
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Concept::class),
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
     * @Error(code="concept-create-notation-not-unique",
     *        status=409,
     *        description="The given skos:notation is not unique within the given conceptScheme",
     *        fields={"conceptscheme","notation"}
     * )
     *
     * @ErrorInherit(class=ApiFilter::class              , method="__construct"         )
     * @ErrorInherit(class=ApiRequest::class             , method="__construct"         )
     * @ErrorInherit(class=ApiRequest::class             , method="getAuthentication"   )
     * @ErrorInherit(class=ApiRequest::class             , method="getFormat"           )
     * @ErrorInherit(class=ApiRequest::class             , method="getGraph"            )
     * @ErrorInherit(class=Authentication::class         , method="requireAdministrator")
     * @ErrorInherit(class=Concept::class                , method="getProperty"         )
     * @ErrorInherit(class=Concept::class                , method="getValue"            )
     * @ErrorInherit(class=Concept::class                , method="iri"                 )
     * @ErrorInherit(class=Concept::class                , method="save"                )
     * @ErrorInherit(class=ConceptRepository::class      , method="__construct"         )
     * @ErrorInherit(class=ConceptRepository::class      , method="fromGraph"           )
     * @ErrorInherit(class=ConceptSchemeRepository::class, method="__construct"         )
     * @ErrorInherit(class=ConceptSchemeRepository::class, method="findByIri"           )
     * @ErrorInherit(class=InstitutionRepository::class  , method="__construct"         )
     * @ErrorInherit(class=InstitutionRepository::class  , method="findOneBy"           )
     * @ErrorInherit(class=InternalResourceId::class     , method="__construct"         )
     * @ErrorInherit(class=Iri::class                    , method="__construct"         )
     * @ErrorInherit(class=Iri::class                    , method="getUri"              )
     * @ErrorInherit(class=SolrFilterProcessor::class    , method="__construct"         )
     * @ErrorInherit(class=ListResponse::class           , method="__construct"         )
     * @ErrorInherit(class=SetRepository::class          , method="findByIri"           )
     * @ErrorInherit(class=TripleFactory::class          , method="triplesFromGraph"    )
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
        $triples  = TripleFactory::triplesFromGraph($graph);
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

        foreach ($concepts as $concept) {
            $conceptSchemes = $concept->getProperty(Skos::IN_SCHEME);

            // Ensure the conceptschemes exist
            foreach ($conceptSchemes as $conceptSchemeIri) {
                $conceptScheme = $conceptSchemeRepository->findByIri($conceptSchemeIri);
                if (is_null($conceptScheme)) {
                    throw new ApiException('concept-create-conceptscheme-does-not-exist', [
                        'conceptscheme' => $conceptSchemeIri->getUri(),
                    ]);
                }
            }

            // Ensure unique notation in conceptschemes
            $notation = $concept->getValue(Skos::NOTATION);
            foreach ($conceptSchemes as $conceptSchemeIri) {
                $numFound = 0;
                $iri      = $conceptSchemeIri->getUri();

                // Fetch notation & scheme combination
                $conceptRepository->search('*:*', 20, 0, $numFound, null, $filter = [
                    'notationFilter' => sprintf('max_numeric_notation:"%s"', $notation),
                    'schemeFilter'   => sprintf('inScheme:"%s"', str_replace('"', '\\"', str_replace('\\', '\\\\', $iri))),
                ]);

                if ($numFound) {
                    throw new ApiException('concept-create-notation-not-unique', [
                        'conceptscheme' => $iri,
                        'notation'      => $notation,
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
     * @OA\Summary("Update one or more concepts (FULL rewrite)")
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
     *     items=@OA\Schema\ObjectLiteral(class=Concept::class),
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Concept::class),
     *     ),
     *   }),
     * )
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
     *
     * @ErrorInherit(class=ApiRequest::class             , method="__construct"         )
     * @ErrorInherit(class=ApiRequest::class             , method="getAuthentication"   )
     * @ErrorInherit(class=ApiRequest::class             , method="getFormat"           )
     * @ErrorInherit(class=ApiRequest::class             , method="getGraph"            )
     * @ErrorInherit(class=Authentication::class         , method="getUser"             )
     * @ErrorInherit(class=Authentication::class         , method="requireAdministrator")
     * @ErrorInherit(class=Concept::class                , method="errors"              )
     * @ErrorInherit(class=Concept::class                , method="exists"              )
     * @ErrorInherit(class=Concept::class                , method="getProperty"         )
     * @ErrorInherit(class=Concept::class                , method="getValue"            )
     * @ErrorInherit(class=Concept::class                , method="iri"                 )
     * @ErrorInherit(class=Concept::class                , method="setValue"            )
     * @ErrorInherit(class=Concept::class                , method="update"              )
     * @ErrorInherit(class=ConceptRepository::class      , method="__construct"         )
     * @ErrorInherit(class=ConceptRepository::class      , method="fromGraph"           )
     * @ErrorInherit(class=ConceptSchemeRepository::class, method="__construct"         )
     * @ErrorInherit(class=ConceptSchemeRepository::class, method="findByIri"           )
     * @ErrorInherit(class=InstitutionRepository::class  , method="__construct"         )
     * @ErrorInherit(class=InstitutionRepository::class  , method="findOneBy"           )
     * @ErrorInherit(class=InternalResourceId::class     , method="__construct"         )
     * @ErrorInherit(class=Iri::class                    , method="__construct"         )
     * @ErrorInherit(class=Iri::class                    , method="getUri"              )
     * @ErrorInherit(class=ListResponse::class           , method="__construct"         )
     * @ErrorInherit(class=SetRepository::class          , method="__construct"         )
     * @ErrorInherit(class=SetRepository::class          , method="findByIri"           )
     * @ErrorInherit(class=User::class                   , method="iri"                 )
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
     * @Route(path="/concept/{id}.{format?}", methods={"DELETE"})
     *
     * @OA\Summary("Delete a single concept using it's identifier")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="id",
     *     in="path",
     *     example="1911",
     *   ),
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Concept::class),
     *     ),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @Error(code="concept-delete-invalid-permissions-user-missing",
     *        status=404,
     *        description="The administrator role was present but the authenticated user was missing"
     * )
     * @Error(code="concept-delete-not-an-orphan",
     *        status=400,
     *        description="The concept to delete still has relations"
     * )
     *
     * @ErrorInherit(class=ApiRequest::class        , method="__construct"         )
     * @ErrorInherit(class=ApiRequest::class        , method="getAuthentication"   )
     * @ErrorInherit(class=ApiRequest::class        , method="getFormat"           )
     * @ErrorInherit(class=Authentication::class    , method="getUser"             )
     * @ErrorInherit(class=Authentication::class    , method="requireAdministrator")
     * @ErrorInherit(class=Concept::class           , method="deleteSoft"          )
     * @ErrorInherit(class=Concept::class           , method="isOrphan"            )
     * @ErrorInherit(class=ConceptController::class , method="getConcept"          )
     * @ErrorInherit(class=ConceptRepository::class , method="__construct"         )
     * @ErrorInherit(class=InternalResourceId::class, method="__construct"         )
     * @ErrorInherit(class=ScalarResponse::class    , method="__construct"         )
     */
    public function deleteConcept(
        InternalResourceId $id,
        ApiRequest $apiRequest,
        ConceptRepository $conceptRepository,
        LabelRepository $labelRepository
    ): ScalarResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();
        $user = $auth->getUser();
        if (is_null($user)) {
            // TODO: security is severely compromised if this is thrown
            throw new ApiException('concept-delete-invalid-permissions-user-missing');
        }

        // Fetch the concept we're deleting
        /** @var Concept $concept */
        $concept = $this->getConcept($id, $apiRequest, $conceptRepository, $labelRepository)->doc();

        // Ensure the concept doesn't have relations
        // TODO: delete despite having relations? (a.k.a. orphan check override)
        if (!$concept->isOrphan()) {
            throw new ApiException('concept-delete-not-an-orphan');
        }

        // Throw any update errors
        $errors = $concept->deleteSoft($user);
        if ($errors) {
            throw new ApiException($errors[0]);
        }

        // Return the concept we just deleted
        return new ScalarResponse(
            $concept,
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/autocomplete.{format?}", methods={"GET"})
     *
     * @OA\Summary("Loose label search on concepts")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="id",
     *     in="path",
     *     example="1911",
     *   ),
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Concept::class),
     *     ),
     *   }),
     * )
     *
     * @ErrorInherit(class=ApiFilter::class          , method="__construct"              )
     * @ErrorInherit(class=ApiRequest::class         , method="__construct"              )
     * @ErrorInherit(class=ApiRequest::class         , method="getFormat"                )
     * @ErrorInherit(class=ApiRequest::class         , method="getLimit"                 )
     * @ErrorInherit(class=ApiRequest::class         , method="getOffset"                )
     * @ErrorInherit(class=ApiRequest::class         , method="getParameter"             )
     * @ErrorInherit(class=ConceptController::class  , method="buildConceptFilters"      )
     * @ErrorInherit(class=ConceptController::class  , method="buildProjectionParameters")
     * @ErrorInherit(class=ConceptController::class  , method="buildSelectionParameters" )
     * @ErrorInherit(class=ConceptRepository::class  , method="__construct"              )
     * @ErrorInherit(class=ConceptRepository::class  , method="fullSolrSearch"           )
     * @ErrorInherit(class=ListResponse::class       , method="__construct"              )
     * @ErrorInherit(class=SolrFilterProcessor::class, method="__construct"              )
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
