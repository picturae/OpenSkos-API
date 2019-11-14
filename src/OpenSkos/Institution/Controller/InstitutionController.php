<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution\Controller;

use App\Annotation\Error;
use App\Entity\User;
use App\Exception\ApiException;
use App\Ontology\OpenSkos;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Institution\InstitutionRepository;
use App\OpenSkos\InternalResourceId;
use App\Rdf\AbstractRdfDocument;
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
     * @Route(path="/institutions.{format?}", methods={"POST"})
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
        if (is_null($institutions)) {
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
     * @throws ApiException
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
        if (is_null($institutions)) {
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
            $institution->setValue($modifier, $user->getUri());
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
