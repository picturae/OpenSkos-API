<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution\Controller;

use App\Annotation\Error;
use App\Annotation\ErrorInherit;
use App\Annotation\OA;
use App\Entity\User;
use App\Exception\ApiException;
use App\Ontology\OpenSkos;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Institution\Institution;
use App\OpenSkos\Institution\InstitutionRepository;
use App\OpenSkos\InternalResourceId;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use App\Security\Authentication;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class InstitutionController
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
     * @Route(path="/institutions.{format?}", methods={"GET"})
     *
     * @OA\Summary("Retreive all (filtered) institutions")
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
     *       items=@OA\Schema\ObjectLiteral(class=Institution::class),
     *     ),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @Error(code="institution-getall-filter-institutions-not-applicable",
     *        status=400,
     *        description="An institutions filter was given in the request but it is not supported on this endpoint"
     * )
     * @Error(code="institution-getall-filter-sets-not-applicable",
     *        status=400,
     *        description="A sets filter was given in the request but it is not supported on this endpoint"
     * )
     *
     * @ErrorInherit(class=ApiRequest::class           , method="__construct"    )
     * @ErrorInherit(class=ApiRequest::class           , method="getFormat"      )
     * @ErrorInherit(class=ApiRequest::class           , method="getInstitutions")
     * @ErrorInherit(class=ApiRequest::class           , method="getLimit"       )
     * @ErrorInherit(class=ApiRequest::class           , method="getOffset"      )
     * @ErrorInherit(class=ApiRequest::class           , method="getSets"        )
     * @ErrorInherit(class=InstitutionRepository::class, method="__construct"    )
     * @ErrorInherit(class=ListResponse::class         , method="__construct"    )
     */
    public function getInstitutions(
        ApiRequest $apiRequest,
        InstitutionRepository $repository
    ): ListResponse {
        $param_institutions = $apiRequest->getInstitutions();
        if (isset($param_institutions) && 0 !== count($param_institutions)) {
            throw new ApiException('institution-getall-filter-institutions-not-applicable');
        }

        /* According to the specs, throw a 400 when asked for sets */
        $param_sets = $apiRequest->getSets();
        if (isset($param_sets) && 0 !== count($param_sets)) {
            throw new ApiException('institution-getall-filter-sets-not-applicable');
        }

        $institutions = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit());

        return new ListResponse(
            $institutions,
            count($institutions),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/institution/{id}.{format?}", methods={"GET"})
     *
     * @OA\Summary("Retreive an institution using it's identifier")
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
     *       items=@OA\Schema\ObjectLiteral(class=Institution::class),
     *     ),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @Error(code="institution-getone-not-found",
     *        status=404,
     *        description="The requested institution could not be retreived",
     *        fields={"id"}
     * )
     *
     * @ErrorInherit(class=ApiRequest::class           , method="__construct")
     * @ErrorInherit(class=ApiRequest::class           , method="getFormat"  )
     * @ErrorInherit(class=InstitutionRepository::class, method="__construct")
     * @ErrorInherit(class=InstitutionRepository::class, method="findOneBy"  )
     * @ErrorInherit(class=InstitutionRepository::class, method="getByUuid"  )
     * @ErrorInherit(class=InternalResourceId::class   , method="__construct")
     * @ErrorInherit(class=InternalResourceId::class   , method="__toString" )
     * @ErrorInherit(class=Iri::class                  , method="__construct")
     * @ErrorInherit(class=ScalarResponse::class       , method="__construct")
     */
    public function getInstitution(
        InternalResourceId $id,
        ApiRequest $apiRequest,
        InstitutionRepository $repository
    ): ScalarResponse {
        $institution = $repository->findOneBy(
            new Iri(OpenSkos::CODE),
            $id
        );

        if (null === $institution) {
            $institution = $repository->getByUuid($id);
        }

        if (null === $institution) {
            throw new ApiException('institution-getone-not-found', [
                'id' => $id->__toString(),
            ]);
        }

        return new ScalarResponse($institution, $apiRequest->getFormat());
    }
    /**
     * @Route(path="/institution", methods={"GET"})
     *
     * @OA\Summary("Retreive an institution using it's identifier")
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
     *       items=@OA\Schema\ObjectLiteral(class=Institution::class),
     *     ),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @Error(code="institution-getone-not-found",
     *        status=404,
     *        description="The requested institution could not be retreived",
     *        fields={"id"}
     * )
     *
     * @ErrorInherit(class=ApiRequest::class           , method="__construct")
     * @ErrorInherit(class=ApiRequest::class           , method="getFormat"  )
     * @ErrorInherit(class=InstitutionRepository::class, method="__construct")
     * @ErrorInherit(class=InstitutionRepository::class, method="findOneBy"  )
     * @ErrorInherit(class=InstitutionRepository::class, method="getByUuid"  )
     * @ErrorInherit(class=InternalResourceId::class   , method="__construct")
     * @ErrorInherit(class=InternalResourceId::class   , method="__toString" )
     * @ErrorInherit(class=Iri::class                  , method="__construct")
     * @ErrorInherit(class=ScalarResponse::class       , method="__construct")
     */
    public function getInstitutionByForeignId(
        ApiRequest $apiRequest,
        InstitutionRepository $repository
    ): ScalarResponse {

        $iri = $apiRequest->getForeignUri();
        $institution = $repository->get( new Iri($iri) );

        if (null === $institution) {
            throw new ApiException('institution-getone-not-found', [
                'uri' => $iri,
            ]);
        }

        return new ScalarResponse($institution, $apiRequest->getFormat());
    }

    /**
     * @Route(path="/institutions.{format?}", methods={"POST"})
     *
     * @OA\Summary("Create one or more new institutions")
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
     *     items=@OA\Schema\ObjectLiteral(class=Institution::class),
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Institution::class),
     *     ),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @Error(code="institution-create-empty-or-corrupt-body",
     *        status=400,
     *        description="The body passed to this endpoint was either missing or corrupt"
     * )
     * @Error(code="institution-create-already-exists",
     *        status=409,
     *        description="An institution with the given iri already exists",
     *        fields={"iri"}
     * )
     *
     * @ErrorInherit(class=ApiRequest::class           , method="__construct"         )
     * @ErrorInherit(class=ApiRequest::class           , method="getAuthentication"   )
     * @ErrorInherit(class=ApiRequest::class           , method="getFormat"           )
     * @ErrorInherit(class=ApiRequest::class           , method="getGraph"            )
     * @ErrorInherit(class=Authentication::class       , method="requireAdministrator")
     * @ErrorInherit(class=Institution::class          , method="errors"              )
     * @ErrorInherit(class=Institution::class          , method="exists"              )
     * @ErrorInherit(class=Institution::class          , method="iri"                 )
     * @ErrorInherit(class=Institution::class          , method="save"                )
     * @ErrorInherit(class=InstitutionRepository::class, method="__construct"         )
     * @ErrorInherit(class=InstitutionRepository::class, method="fromGraph"           )
     * @ErrorInherit(class=Iri::class                  , method="getUri"              )
     * @ErrorInherit(class=ListResponse::class         , method="__construct"         )
     */
    public function postInstitution(
        ApiRequest $apiRequest,
        InstitutionRepository $repository
    ): ListResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();

        // Load data into institutions
        $graph        = $apiRequest->getGraph();
        $institutions = $repository->fromGraph($graph);
        if (is_null($institutions)||(!count($institutions))) {
            throw new ApiException('institution-create-empty-or-corrupt-body');
        }

        // Check if the resources already exist
        foreach ($institutions as $institution) {
            if ($institution->exists()) {
                throw new ApiException('institution-create-already-exists', [
                    'iri' => $institution->iri()->getUri(),
                ]);
            }
        }

        // Validate all given resources
        $errors = [];
        foreach ($institutions as $institution) {
            $errors = array_merge($errors, $institution->errors());
        }
        if (count($errors)) {
            foreach ($errors as $error) {
                throw new ApiException($error);
            }
        }

        // Save all given institutions
        foreach ($institutions as $institution) {
            $institution->save();
        }

        // Return re-fetched institutions
        return new ListResponse(
            $institutions,
            count($institutions),
            0,
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/institution/{id}.{format?}", methods={"DELETE"})
     *
     * @OA\Summary("Delete a single institution using it's identifier")
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
     *       items=@OA\Schema\ObjectLiteral(class=Institution::class),
     *     ),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @ErrorInherit(class=ApiRequest::class           , method="__construct"         )
     * @ErrorInherit(class=ApiRequest::class           , method="getAuthentication"   )
     * @ErrorInherit(class=ApiRequest::class           , method="getFormat"           )
     * @ErrorInherit(class=Authentication::class       , method="requireAdministrator")
     * @ErrorInherit(class=Institution::class          , method="delete"              )
     * @ErrorInherit(class=InstitutionController::class, method="getInstitution"      )
     * @ErrorInherit(class=InstitutionRepository::class, method="__construct"         )
     * @ErrorInherit(class=InternalResourceId::class   , method="__construct"         )
     * @ErrorInherit(class=ScalarResponse::class       , method="__construct"         )
     */
    public function deleteInstitution(
        InternalResourceId $id,
        ApiRequest $apiRequest,
        InstitutionRepository $repository
    ): ScalarResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();

        // Fetch the institution we're deleting
        /** @var AbstractRdfDocument $institution */
        $institution = $this->getInstitution($id, $apiRequest, $repository)->doc();

        $errors = $institution->delete();
        if ($errors) {
            throw new ApiException($errors[0]);
        }

        return new ScalarResponse(
            $institution,
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/institutions.{format?}", methods={"PUT"})
     *
     * @OA\Summary("Update one or more institutions (FULL rewrite)")
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
     *     items=@OA\Schema\ObjectLiteral(class=Institution::class),
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Institution::class),
     *     ),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @Error(code="institution-update-empty-or-corrupt-body",
     *        status=400,
     *        description="The body passed to this endpoint was either missing or corrupt"
     * )
     * @Error(code="institution-update-does-not-exist",
     *        status=400,
     *        description="The institution with the given iri does not exist",
     *        fields={"iri"}
     * )
     *
     * @ErrorInherit(class=ApiRequest::class           , method="__construct"         )
     * @ErrorInherit(class=ApiRequest::class           , method="getAuthentication"   )
     * @ErrorInherit(class=ApiRequest::class           , method="getFormat"           )
     * @ErrorInherit(class=ApiRequest::class           , method="getGraph"            )
     * @ErrorInherit(class=Authentication::class       , method="getUser"             )
     * @ErrorInherit(class=Authentication::class       , method="requireAdministrator")
     * @ErrorInherit(class=Institution::class          , method="errors"              )
     * @ErrorInherit(class=Institution::class          , method="exists"              )
     * @ErrorInherit(class=Institution::class          , method="iri"                 )
     * @ErrorInherit(class=Institution::class          , method="setValue"            )
     * @ErrorInherit(class=Institution::class          , method="update"              )
     * @ErrorInherit(class=InstitutionRepository::class, method="__construct"         )
     * @ErrorInherit(class=InstitutionRepository::class, method="fromGraph"           )
     * @ErrorInherit(class=Iri::class                  , method="getUri"              )
     * @ErrorInherit(class=ListResponse::class         , method="__construct"         )
     * @ErrorInherit(class=User::class                 , method="iri"                 )
     */
    public function putInstitution(
        ApiRequest $apiRequest,
        InstitutionRepository $repository
    ): ListResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();
        /** @var User $user */
        $user = $auth->getUser();

        // Load data into institutions
        $graph        = $apiRequest->getGraph();
        $institutions = $repository->fromGraph($graph);
        if (is_null($institutions)||(!count($institutions))) {
            throw new ApiException('institution-update-empty-or-corrupt-body');
        }

        // Validate all given resources
        $errors = [];
        foreach ($institutions as $institution) {
            if (!$institution->exists()) {
                throw new ApiException('institution-update-does-not-exist', [
                    'iri' => $institution->iri()->getUri(),
                ]);
            }
            $errors = array_merge($errors, $institution->errors());
        }
        if (count($errors)) {
            foreach ($errors as $error) {
                throw new ApiException($error);
            }
        }

        // Rebuild all given institutions
        $modifier = new Iri(OpenSkos::MODIFIED_BY);
        foreach ($institutions as $institution) {
            $institution->setValue($modifier, $user->iri());
            $errors = $institution->update();
            if ($errors) {
                throw new ApiException($errors[0]);
            }
        }

        return new ListResponse(
            $institutions,
            count($institutions),
            0,
            $apiRequest->getFormat()
        );
    }
}
