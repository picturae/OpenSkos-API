<?php

declare(strict_types=1);

namespace App\OpenSkos\RelationType\Controller;

use App\Annotation\ErrorInherit;
use App\Annotation\OA;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\RelationType\RelationType;
use App\Rdf\Iri;
use App\Rest\DirectGraphResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class RelationTypeController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Role constructor.
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @Route(path="/relationtypes.{format?}", methods={"GET"})
     *
     * @OA\Summary("Retreive all available relation types")
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
     *         @OA\Schema\StringLiteral(name="rdfs:subPropertyOf", description="What this relation type is a sub-version of"),
     *         @OA\Schema\StringLiteral(name="rdfs:domain"                                                                  ),
     *         @OA\Schema\StringLiteral(name="rdfs:range"                                                                   ),
     *       }),
     *     ),
     *   }),
     * )
     *
     * @ErrorInherit(class=ApiRequest::class         , method="__construct")
     * @ErrorInherit(class=ApiRequest::class         , method="__construct")
     * @ErrorInherit(class=DirectGraphResponse::class, method="__construct")
     * @ErrorInherit(class=RelationType::class       , method="vocabulary" )
     */
    public function getRelationTypes(
        ApiRequest $apiRequest
    ): DirectGraphResponse {
        return new DirectGraphResponse(
            RelationType::vocabulary(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/relationtype/{id}.{format?}", methods={"GET"})
     *
     * @OA\Summary("Retreive a single relation type")
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
     *         @OA\Schema\StringLiteral(name="rdfs:subPropertyOf", description="What this relation type is a sub-version of"),
     *         @OA\Schema\StringLiteral(name="rdfs:domain"                                                                  ),
     *         @OA\Schema\StringLiteral(name="rdfs:range"                                                                   ),
     *       }),
     *     ),
     *   }),
     * )
     *
     * @ErrorInherit(class=ApiRequest::class         , method="__construct")
     * @ErrorInherit(class=ApiRequest::class         , method="getFormat"  )
     * @ErrorInherit(class=DirectGraphResponse::class, method="__construct")
     * @ErrorInherit(class=Iri::class                , method="getUri"     )
     * @ErrorInherit(class=RelationType::class       , method="vocabulary" )
     */
    public function getRelationType(
        string $id,
        ApiRequest $apiRequest
    ): DirectGraphResponse {
        // Build source data
        $graph        = RelationType::vocabulary();
        $relationtype = null;
        $options      = [
            $graph->resource($id),
            $graph->resource('openskos:'.$id),
        ];

        // Simple 'exists' filter
        foreach ($options as $resource) {
            if (is_null($resource->type())) {
                continue;
            }
            $relationtype = $resource;
        }

        // TODO: Handle 404 in a nicer way than an empty graph
        // TODO: Add a better method to filter
        $resources = $graph->resources();
        foreach ($resources as $key => $resource) {
            if (!is_null($relationtype)) {
                if ($graph->resource($key)->getUri() === $relationtype->getUri()) {
                    continue;
                }
            }
            foreach ($resource->properties() as $property) {
                $graph->delete($resource, $property, null);
            }
        }

        return new DirectGraphResponse(
            $graph,
            $apiRequest->getFormat()
        );
    }
}
