<?php

declare(strict_types=1);

namespace App\Institution\Controller;

use App\Institution\InstitutionRepository;
use App\OpenSkos\ApiRequest;
use App\Rest\ListResponse;
use Symfony\Component\HttpFoundation\Response;
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
     * @return Response
     */
    public function institutions(ApiRequest $apiRequest, InstitutionRepository $repository): Response
    {
        $institutions = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit());

        $list = new ListResponse($institutions, count($institutions), $apiRequest->getOffset());

        $res = $this->serializer->serialize($list, $apiRequest->getFormat());

        $formatOut = $apiRequest->getReturnContentType();

        $response = new Response($res, Response::HTTP_OK, []);
        $response->headers->set('Content-Type', $formatOut);

        return $response;
    }
}
