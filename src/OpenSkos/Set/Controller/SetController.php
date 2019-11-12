<?php

declare(strict_types=1);

namespace App\OpenSkos\Set\Controller;

use App\Annotation\Error;
use App\Exception\ApiException;
use App\Ontology\OpenSkos;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Filters\FilterProcessor;
use App\OpenSkos\Institution\InstitutionRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Set\SetRepository;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     */
    public function getSets(
        ApiRequest $apiRequest,
        SetRepository $repository,
        FilterProcessor $filterProcessor
    ): ListResponse {
        $param_institutions = $apiRequest->getInstitutions();
        $full_filter = $filterProcessor->buildInstitutionFilters($param_institutions);

        /* According to the specs, throw a 400 when asked for sets */
        $param_sets = $apiRequest->getSets();
        if (isset($param_sets) && 0 !== count($param_sets)) {
            throw new BadRequestHttpException('Sets filter is not applicable here.');
        }

        $sets = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter);

        return new ListResponse(
            $sets,
            count($sets),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/set/{id}.{format?}", methods={"GET"})
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
            throw new NotFoundHttpException("The institution $id could not be retreived.");
        }

        return new ScalarResponse($set, $apiRequest->getFormat());
    }

    /**
     * @Route(path="/sets.{format?}", methods={"POST"})
     *
     * @throws ApiException
     *
     * @Error(code="set-create-empty-or-corrupt-body",
     *        status=400,
     *        description="The body passed to this endpoint was either missing or corrupt"
     * )
     * @Error(code="set-create-already-exists",
     *        status=400,
     *        description="A set with the given iri already exists",
     *        fields={"iri"}
     * )
     * @Error(code="set-create-tenant-does-not-exist",
     *        status=400,
     *        description="The given tenant to create a set for does not exist",
     *        fields={"tenant"}
     * )
     */
    public function postSet(
        ApiRequest $apiRequest,
        SetRepository $setRepository,
        InstitutionRepository $institutionRepository
    ): ListResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();

        // Load data into institutions
        $graph = $apiRequest->getGraph();
        $sets = $setRepository->fromGraph($graph);
        if (is_null($sets)) {
            throw new ApiException('set-create-empty-or-corrupt-body');
        }

        // Check if the resources already exist
        foreach ($sets as $set) {
            if ($set->exists()) {
                throw new ApiException('set-create-already-exists', [
                    'iri' => $set->iri()->getUri(),
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
            $tenant = $institutionRepository->findOneBy(
                new Iri(OpenSkos::CODE),
                new InternalResourceId($tenantCode)
            );
            if (is_null($tenant)) {
                throw new ApiException('set-create-tenant-does-not-exist', [
                    'tenant' => $tenantCode,
                ]);
            }
        }

        // Save all given institutions
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
}
