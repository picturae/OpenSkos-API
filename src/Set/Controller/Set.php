<?php

declare(strict_types=1);

namespace App\Set\Controller;

use App\Set\SetRepository;
use App\Ontology\OpenSkos;
use App\OpenSkos\ApiRequest;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class Set
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
     * @Route(path="/sets", methods={"GET"})
     *
     * @param ApiRequest    $apiRequest
     * @param SetRepository $repository
     *
     * @return Response
     */
    public function sets(ApiRequest $apiRequest, SetRepository $repository): Response
    {
        $sets = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit());

        $list = new ListResponse($sets, count($sets), $apiRequest->getOffset());

        $res = $this->serializer->serialize($list, $apiRequest->getFormat());

        $formatOut = $apiRequest->getReturnContentType();

        $response = new Response($res, Response::HTTP_OK, []);
        $response->headers->set('Content-Type', $formatOut);

        return $response;
    }

    /**
     * @Route(path="/set/{id}", methods={"GET"})
     *
     * @param ApiRequest    $apiRequest
     * @param SetRepository $repository
     *
     * @return Response
     */
    public function set(Request $request, ApiRequest $apiRequest, SetRepository $repository): Response
    {
        $id = $request->get('id');

        $set = $repository->findOneBy(
            new Iri(OpenSkos::SET),
            new Iri(OpenSkos::CODE),
            $id
        );

        if (!$set) {
            throw new NotFoundHttpException("The set $id could not be retreived.");
        }
        $l2Object = $set->getLevel2Object();

        $list = new ScalarResponse($set);

        $res = $this->serializer->serialize($list, $apiRequest->getFormat());

        $formatOut = $apiRequest->getReturnContentType();

        $response = new Response($res, Response::HTTP_OK, []);
        $response->headers->set('Content-Type', $formatOut);

        return $response;
    }
}
