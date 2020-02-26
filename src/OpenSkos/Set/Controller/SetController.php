<?php

declare(strict_types=1);

namespace App\OpenSkos\Set\Controller;

use App\Annotation\Error;
use App\Annotation\ErrorInherit;
use App\Annotation\OA;
use App\Entity\User;
use App\Exception\ApiException;
use App\Ontology\OpenSkos;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Filters\FilterProcessor;
use App\OpenSkos\Institution\InstitutionRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Set\Set;
use App\OpenSkos\Set\SetRepository;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use App\Security\Authentication;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class SetController
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
     * @Route(path="/sets.{format?}", methods={"GET"})
     *
     * @OA\Summary("Retreive all (filtered) sets")
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
     *       items=@OA\Schema\ObjectLiteral(class=Set::class),
     *     ),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @Error(code="set-getall-sets-filter",
     *        status=400,
     *        description="A 'sets' filter was given but is not applicable to this endpoint"
     * )
     * @Error(code="set-getall-institution-filter-not-found",
     *        status=404,
     *        description="A 'institutions' filter was given but the given identifiers could not be found",
     *        fields={"given"},
     * )
     *
     * @ErrorInherit(class=ApiRequest::class           , method="__construct"            )
     * @ErrorInherit(class=ApiRequest::class           , method="getFormat"              )
     * @ErrorInherit(class=ApiRequest::class           , method="getInstitutions"        )
     * @ErrorInherit(class=ApiRequest::class           , method="getLimit"               )
     * @ErrorInherit(class=ApiRequest::class           , method="getOffset"              )
     * @ErrorInherit(class=ApiRequest::class           , method="getSets"                )
     * @ErrorInherit(class=FilterProcessor::class      , method="__construct"            )
     * @ErrorInherit(class=FilterProcessor::class      , method="buildInstitutionFilters")
     * @ErrorInherit(class=InstitutionRepository::class, method="__construct"            )
     * @ErrorInherit(class=InstitutionRepository::class, method="findOneBy"              )
     * @ErrorInherit(class=InternalResourceId::class   , method="__construct"            )
     * @ErrorInherit(class=Iri::class                  , method="__construct"            )
     * @ErrorInherit(class=ListResponse::class         , method="__construct"            )
     * @ErrorInherit(class=SetRepository::class        , method="__construct"            )
     * @ErrorInherit(class=SetRepository::class        , method="all"                    )
     */
    public function getAllSets(
        ApiRequest $apiRequest,
        SetRepository $setRepository,
        InstitutionRepository $institutionRepository,
        FilterProcessor $filterProcessor
    ): ListResponse {
        $param_institutions = $apiRequest->getInstitutions();
        $full_filter        = $filterProcessor->buildInstitutionFilters($param_institutions);

        // Verify the given institution(s) exist
        foreach ($param_institutions as $institutionCode) {
            $institution = $institutionRepository->findOneBy(
                new Iri(OpenSkos::CODE),
                new InternalResourceId($institutionCode)
            );
            if (!$institution) {
                throw new ApiException('set-getall-institution-filter-not-found', [
                    'given' => $institutionCode,
                ]);
            }
        }

        /* According to the specs, throw a 400 when asked for sets */
        $param_sets = $apiRequest->getSets();
        if (isset($param_sets) && (0 !== count($param_sets))) {
            throw new ApiException('set-getall-sets-filter');
        }

        $sets = $setRepository->all($apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter);

        return new ListResponse(
            $sets,
            count($sets),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/set/{id}.{format?}", methods={"GET"})
     *
     * @OA\Summary("Retreive a set using it's identifier")
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
     *       items=@OA\Schema\ObjectLiteral(class=Set::class),
     *     ),
     *   }),
     * )
     *
     * @Error(code="set-getone-not-found",
     *        status=404,
     *        description="The requested set could not be found",
     *        fields={"iri"}
     * )
     *
     * @ErrorInherit(class=ApiRequest::class        , method="__construct")
     * @ErrorInherit(class=ApiRequest::class        , method="getFormat"  )
     * @ErrorInherit(class=InternalResourceId::class, method="__construct")
     * @ErrorInherit(class=InternalResourceId::class, method="id"         )
     * @ErrorInherit(class=Iri::class               , method="__construct")
     * @ErrorInherit(class=ScalarResponse::class    , method="__construct")
     * @ErrorInherit(class=SetRepository::class     , method="__construct")
     * @ErrorInherit(class=SetRepository::class     , method="findOneBy"  )
     */
    public function getSet(
        InternalResourceId $id,
        ApiRequest $apiRequest,
        SetRepository $repository
    ): ScalarResponse {
        $set = $repository->findOneBy(
            new Iri(OpenSkos::CODE),
            $id
        );

        if (null === $set) {
            throw new ApiException('set-getone-not-found', [
                'iri' => $id->id(),
            ]);
        }

        return new ScalarResponse($set, $apiRequest->getFormat());
    }

    /**
     * @Route(path="/sets.{format?}", methods={"POST"})
     *
     * @OA\Summary("Create one or more new sets")
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
     *     items=@OA\Schema\ObjectLiteral(class=Set::class),
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Set::class),
     *     ),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @Error(code="set-create-empty-or-corrupt-body",
     *        status=400,
     *        description="The body passed to this endpoint was either missing or corrupt"
     * )
     * @Error(code="set-create-already-exists",
     *        status=409,
     *        description="A set with the given iri or uuid already exists",
     *        fields={"iri","uuid"}
     * )
     * @Error(code="set-create-already-exists-duplicate-base-uri",
     *        status=409,
     *        description="A set with the given baseUri already exists",
     *        fields={"baseUri"}
     * )
     * @Error(code="set-create-tenant-does-not-exist",
     *        status=400,
     *        description="The given tenant to create a set for does not exist",
     *        fields={"tenant"}
     * )
     *
     * @ErrorInherit(class=ApiRequest::class           , method="__construct"         )
     * @ErrorInherit(class=ApiRequest::class           , method="getAuthentication"   )
     * @ErrorInherit(class=ApiRequest::class           , method="getFormat"           )
     * @ErrorInherit(class=ApiRequest::class           , method="getGraph"            )
     * @ErrorInherit(class=ApiRequest::class           , method="getOffset"           )
     * @ErrorInherit(class=Authentication::class       , method="requireAdministrator")
     * @ErrorInherit(class=InstitutionRepository::class, method="__construct"         )
     * @ErrorInherit(class=InstitutionRepository::class, method="findOneBy"           )
     * @ErrorInherit(class=InternalResourceId::class   , method="__construct"         )
     * @ErrorInherit(class=Iri::class                  , method="__construct"         )
     * @ErrorInherit(class=Iri::class                  , method="getUri"              )
     * @ErrorInherit(class=ListResponse::class         , method="__construct"         )
     * @ErrorInherit(class=Set::class                  , method="exists"              )
     * @ErrorInherit(class=Set::class                  , method="getValue"            )
     * @ErrorInherit(class=Set::class                  , method="iri"                 )
     * @ErrorInherit(class=Set::class                  , method="save"                )
     * @ErrorInherit(class=SetRepository::class        , method="__construct"         )
     * @ErrorInherit(class=SetRepository::class        , method="findOneBy"           )
     * @ErrorInherit(class=SetRepository::class        , method="fromGraph"           )
     */
    public function postSet(
        ApiRequest $apiRequest,
        SetRepository $setRepository,
        InstitutionRepository $institutionRepository
    ): ListResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();

        // Load data into sets
        $graph = $apiRequest->getGraph();
        $sets  = $setRepository->fromGraph($graph);
        if (is_null($sets)||(!count($sets))) {
            throw new ApiException('set-create-empty-or-corrupt-body');
        }

        // Check if the resources already exist
        foreach ($sets as $set) {
            // Check by iri/uuid
            if ($set->exists()) {
                throw new ApiException('set-create-already-exists', [
                    'iri'  => $set->iri()->getUri(),
                    'uuid' => $set->getValue(OpenSkos::UUID),
                ]);
            }

            // Check by concept-base-uri
            $found = $setRepository->findOneBy(
                new Iri(OpenSkos::CONCEPT_BASE_URI),
                new InternalResourceId($set->getValue(OpenSkos::CONCEPT_BASE_URI)->__toString())
            );
            if ($found) {
                throw new ApiException('set-create-already-exists-duplicate-base-uri', [
                    'baseUri' => $set->getValue(OpenSkos::CONCEPT_BASE_URI)->__toString(),
                ]);
            }
        }

        // Validate all given resources
        $errors = [];
        foreach ($sets as $set) {
            $errors = array_merge($errors, $set->errors());
        }
        if (count($errors)) {
            foreach ($errors as $error) {
                throw new ApiException($error);
            }
        }

        // Ensure the tenants exist
        foreach ($sets as $set) {
            $tenantCode = $set->getValue(OpenSkos::TENANT)->value();
            $tenant     = $institutionRepository->findOneBy(
                new Iri(OpenSkos::CODE),
                new InternalResourceId($tenantCode)
            );
            if (is_null($tenant)) {
                throw new ApiException('set-create-tenant-does-not-exist', [
                    'tenant' => $tenantCode,
                ]);
            }
        }

        // Save all given sets
        foreach ($sets as $set) {
            $set->save();
        }

        return new ListResponse(
            $sets,
            0,
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/set/{id}.{format?}", methods={"DELETE"})
     *
     * @OA\Summary("Delete a single set using it's identifier")
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
     *       items=@OA\Schema\ObjectLiteral(class=Set::class),
     *     ),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @ErrorInherit(class=ApiRequest::class        , method="__construct"         )
     * @ErrorInherit(class=ApiRequest::class        , method="getAuthentication"   )
     * @ErrorInherit(class=ApiRequest::class        , method="getFormat"           )
     * @ErrorInherit(class=Authentication::class    , method="requireAdministrator")
     * @ErrorInherit(class=InternalResourceId::class, method="__construct"         )
     * @ErrorInherit(class=ScalarResponse::class    , method="__construct"         )
     * @ErrorInherit(class=Set::class               , method="delete"              )
     * @ErrorInherit(class=SetController::class     , method="getSet"              )
     * @ErrorInherit(class=SetRepository::class     , method="__construct"         )
     */
    public function deleteSet(
        InternalResourceId $id,
        ApiRequest $apiRequest,
        SetRepository $repository
    ): ScalarResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();

        // Fetch the set we're deleting
        /** @var AbstractRdfDocument $set */
        $set = $this->getSet($id, $apiRequest, $repository)->doc();

        $set->delete();

        return new ScalarResponse(
            $set,
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/sets.{format?}", methods={"PUT"})
     *
     * @OA\Summary("Update one or more sets (FULL rewrite)")
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
     *     items=@OA\Schema\ObjectLiteral(class=Set::class),
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Set::class),
     *     ),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @Error(code="set-update-empty-or-corrupt-body",
     *        status=400,
     *        description="The body passed to this endpoint was either missing or corrupt"
     * )
     * @Error(code="set-update-does-not-exist",
     *        status=400,
     *        description="The set with the given iri does not exist",
     *        fields={"iri"}
     * )
     * @Error(code="set-update-tenant-does-not-exist",
     *        status=400,
     *        description="The given tenant to update a set for does not exist",
     *        fields={"tenant"}
     * )
     *
     * @ErrorInherit(class=ApiRequest::class           , method="__construct"         )
     * @ErrorInherit(class=ApiRequest::class           , method="getAuthentication"   )
     * @ErrorInherit(class=ApiRequest::class           , method="getFormat"           )
     * @ErrorInherit(class=ApiRequest::class           , method="getGraph"            )
     * @ErrorInherit(class=Authentication::class       , method="getUser"             )
     * @ErrorInherit(class=Authentication::class       , method="requireAdministrator")
     * @ErrorInherit(class=InstitutionRepository::class, method="__construct"         )
     * @ErrorInherit(class=InstitutionRepository::class, method="findOneBy"           )
     * @ErrorInherit(class=InternalResourceId::class   , method="__construct"         )
     * @ErrorInherit(class=Iri::class                  , method="__construct"         )
     * @ErrorInherit(class=Iri::class                  , method="getUri"              )
     * @ErrorInherit(class=ListResponse::class         , method="__construct"         )
     * @ErrorInherit(class=Set::class                  , method="errors"              )
     * @ErrorInherit(class=Set::class                  , method="getValue"            )
     * @ErrorInherit(class=Set::class                  , method="iri"                 )
     * @ErrorInherit(class=Set::class                  , method="setValue"            )
     * @ErrorInherit(class=Set::class                  , method="update"              )
     * @ErrorInherit(class=SetRepository::class        , method="__construct"         )
     * @ErrorInherit(class=SetRepository::class        , method="fromGraph"           )
     * @ErrorInherit(class=User::class                 , method="iri"                 )
     */
    public function putSet(
        ApiRequest $apiRequest,
        SetRepository $setRepository,
        InstitutionRepository $institutionRepository
    ): ListResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();
        /** @var User $user */
        $user = $auth->getUser();

        // Load data into sets
        $graph = $apiRequest->getGraph();
        $sets  = $setRepository->fromGraph($graph);
        if (is_null($sets)||(!count($sets))) {
            throw new ApiException('set-update-empty-or-corrupt-body');
        }

        // Validate all given resources
        $errors = [];
        foreach ($sets as $set) {
            if (!$set->exists()) {
                throw new ApiException('set-update-does-not-exist', [
                    'iri' => $set->iri()->getUri(),
                ]);
            }
            $errors = array_merge($errors, $set->errors());
        }
        if (count($errors)) {
            foreach ($errors as $error) {
                throw new ApiException($error);
            }
        }

        // Ensure the tenants exist
        foreach ($sets as $set) {
            $tenantCode = $set->getValue(OpenSkos::TENANT)->value();
            $tenant     = $institutionRepository->findOneBy(
                new Iri(OpenSkos::CODE),
                new InternalResourceId($tenantCode)
            );
            if (is_null($tenant)) {
                throw new ApiException('set-update-tenant-does-not-exist', [
                    'tenant' => $tenantCode,
                ]);
            }
        }

        // Rebuild all given sets
        $modifier = new Iri(OpenSkos::MODIFIED_BY);
        foreach ($sets as $set) {
            $set->setValue($modifier, $user->iri());
            $errors = $set->update();
            if ($errors) {
                throw new ApiException($errors[0]);
            }
        }

        return new ListResponse(
            $sets,
            count($sets),
            0,
            $apiRequest->getFormat()
        );
    }
}
