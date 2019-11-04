<?php

declare(strict_types=1);

namespace App\OpenSkos\RelationType\Controller;

use App\OpenSkos\ApiRequest;
use App\OpenSkos\RelationType\RelationType;
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
     */
    public function getRelationType(
        string $id,
        ApiRequest $apiRequest
    ): DirectGraphResponse {
        // Build source data
        $graph = RelationType::vocabulary();
        $relationtype = null;
        $options = [
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
