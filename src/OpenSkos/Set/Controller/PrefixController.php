<?php

declare(strict_types=1);

namespace App\OpenSkos\Set\Controller;

use App\Annotation\Error;
use App\Annotation\ErrorInherit;
use App\Annotation\OA;
use App\Exception\ApiException;
use App\Ontology\OpenSkos;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Filters\FilterProcessor;
use App\OpenSkos\Set\SetRepository;
use App\Rest\DirectGraphResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

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
     * @OA\Summary("Retreive all available prefixes")
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
     *       items=@OA\Schema\ObjectLiteral(properties={
     *         @OA\Schema\StringLiteral(name="openskos:prefix", description="The available prefix url"),
     *       }),
     *     ),
     *   }),
     * )
     *
     * @Error(code="setprefixcontroller-sets-filter-not-applicable",
     *        status=400,
     *        description="Sets filter is not applicable here"
     * )
     *
     * @ErrorInherit(class=ApiRequest::class         , method="__construct"            )
     * @ErrorInherit(class=ApiRequest::class         , method="getFormat"              )
     * @ErrorInherit(class=ApiRequest::class         , method="getInstitutions"        )
     * @ErrorInherit(class=ApiRequest::class         , method="getOffset"              )
     * @ErrorInherit(class=ApiRequest::class         , method="getLimit"               )
     * @ErrorInherit(class=ApiRequest::class         , method="getSets"                )
     * @ErrorInherit(class=DirectGraphResponse::class, method="__construct"            )
     * @ErrorInherit(class=FilterProcessor::class    , method="__construct"            )
     * @ErrorInherit(class=FilterProcessor::class    , method="buildInstitutionFilters")
     * @ErrorInherit(class=SetRepository::class      , method="__construct"            )
     * @ErrorInherit(class=SetRepository::class      , method="all"                    )
     */
    public function getAllPrefixes(
        ApiRequest $apiRequest,
        SetRepository $repository,
        FilterProcessor $filterProcessor
    ): DirectGraphResponse {
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);

        $param_institutions = $apiRequest->getInstitutions();
        $full_filter        = $filterProcessor->buildInstitutionFilters($param_institutions);

        /* According to the specs, throw a 400 when asked for sets */
        $param_sets = $apiRequest->getSets();
        if (isset($param_sets) && 0 !== count($param_sets)) {
            throw new ApiException('setprefixcontroller-sets-filter-not-applicable');
        }

        $sets = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter);

        $graph     = new \EasyRdf_Graph();
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
}
