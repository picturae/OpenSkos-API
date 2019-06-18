<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution\Controller;

use App\OpenSkos\Institution\InstitutionRepository;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\InternalResourceId;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class Institution
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
     * @Route(path="/institutions", methods={"GET"})
     *
     * @param ApiRequest            $apiRequest
     * @param InstitutionRepository $repository
     *
     * @return ListResponse
     */
    public function institutions(
        ApiRequest $apiRequest,
        InstitutionRepository $repository
    ): ListResponse {
        $institutions = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit());

        return new ListResponse(
            $institutions,
            count($institutions),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/institution/{id}", methods={"GET"})
     *
     * @param InternalResourceId    $id
     * @param ApiRequest            $apiRequest
     * @param InstitutionRepository $repository
     *
     * @return ScalarResponse
     */
    public function institution(
        InternalResourceId $id,
        ApiRequest $apiRequest,
        InstitutionRepository $repository
    ): ScalarResponse {
        $institution = $repository->find($id);

        if (null === $institution) {
            throw new NotFoundHttpException("The institution $id could not be retreived.");
        }

        return new ScalarResponse($institution, $apiRequest->getFormat());
    }
}