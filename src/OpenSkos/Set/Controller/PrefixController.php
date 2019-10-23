<?php

declare(strict_types=1);

namespace App\OpenSkos\Set\Controller;

use App\OpenSkos\Filters\FilterProcessor;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Set\SetRepository;
use App\Ontology\OpenSkos;
use App\OpenSkos\ApiRequest;
use App\Rdf\Iri;
use App\Rest\DirectGraphResponse;
use App\Rest\ScalarResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PrefixController
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
     * @Route(path="/prefixes.{format?}", methods={"GET"})
     *
     * @param ApiRequest      $apiRequest
     * @param SetRepository   $repository
     * @param FilterProcessor $filterProcessor
     *
     * @return DirectGraphResponse
     */
    public function sets(
        ApiRequest $apiRequest,
        SetRepository $repository,
        FilterProcessor $filterProcessor
    ): DirectGraphResponse {
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);

        $param_institutions = $apiRequest->getInstitutions();
        $full_filter = $filterProcessor->buildInstitutionFilters($param_institutions);

        /* According to the specs, throw a 400 when asked for sets */
        $param_sets = $apiRequest->getSets();
        if (isset($param_sets) && 0 !== count($param_sets)) {
            throw new BadRequestHttpException('Sets filter is not applicable here.');
        }

        $param_profile = $apiRequest->getSearchProfile();

        if ($param_profile) {
            if (0 !== count($full_filter)) {
                throw new BadRequestHttpException('Search profile filters cannot be combined with other filters (possible conflicts).');
            }
            $to_apply = [FilterProcessor::ENTITY_INSTITUTION => true];
            $full_filter = $filterProcessor->retrieveSearchProfile($param_profile, $to_apply);
        }
        $sets = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter);

        $graph = new \EasyRdf_Graph();
        $resources = $graph->resource('prefixes');

        foreach ($sets as $set) {
            /* @var Set $set */
            $resources->addLiteral('openskos:prefix', $set->iri());
        }

        return new DirectGraphResponse(
            $graph,
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/set/{id}.{format?}", methods={"GET"})
     *
     * @param InternalResourceId $id
     * @param ApiRequest         $apiRequest
     * @param SetRepository      $repository
     *
     * @return ScalarResponse
     */
    public function set(
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
}
