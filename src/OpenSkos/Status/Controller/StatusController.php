<?php

declare(strict_types=1);

namespace App\OpenSkos\Status\Controller;

use App\Annotation\ErrorInherit;
use App\Annotation\OA;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\OpenSkos\ApiRequest;
use App\Rest\DirectGraphResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class StatusController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * StatusController constructor.
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @Route(path="/statuses.{format?}", methods={"GET"})
     *
     * @OA\Summary("Retreive all available statuses")
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
     *         @OA\Schema\StringLiteral(name="rdf:value", description="One of the allowed values for this openskos:status field"),
     *       }),
     *     ),
     *   }),
     * )
     *
     * @ErrorInherit(class=ApiRequest::class         , method="__construct")
     * @ErrorInherit(class=ApiRequest::class         , method="getFormat"  )
     * @ErrorInherit(class=DirectGraphResponse::class, method="__construct")
     */
    public function getAllStatuses(
        ApiRequest $apiRequest
    ): DirectGraphResponse {
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);
        \EasyRdf_Namespace::set('rdf', Rdf::NAME_SPACE);

        // Define graph structure
        $graph    = new \EasyRdf_Graph('openskos.org');
        $statuses = $graph->resource('openskos:status');
        $statuses->setType('openskos:status');

        // Copy available statuses from static ontology
        foreach (OpenSkos::STATUSES as $status) {
            $statuses->addLiteral('rdf:value', $status);
        }

        return new DirectGraphResponse(
          $graph,
          $apiRequest->getFormat()
        );
    }
}
