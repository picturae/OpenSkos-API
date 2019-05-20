<?php

declare(strict_types=1);

namespace App\Institution\Controller;

use App\Institution\InstitutionRepository;
use App\Rest\ListResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @param InstitutionRepository $repository
     *
     * @return JsonResponse
     */
    public function institutions(InstitutionRepository $repository): JsonResponse
    {
        $institutions = $repository->all();
        $list = new ListResponse($institutions, 0, count($institutions));

        $res = $this->serializer->serialize($list, 'json');

        return new JsonResponse($res, Response::HTTP_OK, [], true);
    }
}
