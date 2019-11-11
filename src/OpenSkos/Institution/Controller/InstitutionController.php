<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution\Controller;

use App\Annotation\Error;
use App\Exception\ApiException;
use App\Ontology\Context;
use App\Ontology\OpenSkos;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Institution\InstitutionRepository;
use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
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
     * @throws ApiException
     *
     * @Error(code="institution-getone-not-found",
     *        status=404,
     *        description="The requested institution could not be retreived",
     *        fields={"id"}
     * )
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
            throw new ApiException('institution-getone-not-found', [
                'id' => $id->__toString(),
            ]);
        }

        return new ScalarResponse($institution, $apiRequest->getFormat());
    }

    /**
     * @Route(path="/institutions.{format?}", methods={"POST"})
     *
     * @throws ApiException
     *
     * @Error(code="institution-create-permission-denied-missing-credentials",
     *        status=401,
     *        description="No credentials were given"
     * )
     * @Error(code="institution-create-permission-denied-invalid-credentials",
     *        status=403,
     *        description="Invalid credentials were given"
     * )
     * @Error(code="institution-create-permission-denied-missing-role-administrator",
     *        status=403,
     *        description="The requested action requires the 'administrator' role while the authenticated user does not posses it"
     * )
     *
     * @Error(code="institution-create-empty-or-corrupt-body",
     *        status=400,
     *        description="The body passed to this endpoint was either missing or corrupt"
     * )
     * @Error(code="institution-create-already-exists",
     *        status=409,
     *        description="The body passed to this endpoint was either missing or corrupt",
     *        fields={"iri"}
     * )
     */
    public function postInstitution(
        ApiRequest $apiRequest,
        InstitutionRepository $repository
    ): ListResponse {
        Context::setupEasyRdf();

        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator('institution-create-');

        // Load data into institutions
        $graph = $apiRequest->getGraph();
        $institutions = $repository->fromGraph($graph);
        if (is_null($institutions)) {
            throw new ApiException('institution-create-empty-or-corrupt-body');
        }

        // Validate all given resources
        $errors = [];
        foreach ($institutions as $institution) {
            $errors = array_merge($errors, $institution->errors());
        }
        if (count($errors)) {
            throw new \Exception(json_encode($errors));
        }

        // Check if the resources already exist
        foreach ($institutions as $institution) {
            if ($institution->exists()) {
                throw new ApiException('institution-create-already-exists', [
                    'iri' => $institution->iri()->getUri(),
                ]);
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
}
