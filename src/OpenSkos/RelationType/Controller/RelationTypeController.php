<?php

declare(strict_types=1);

namespace App\OpenSkos\RelationType\Controller;

use App\Rest\DirectGraphResponse;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\RelationType\RelationType;
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
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @Route(path="/relationtypes", methods={"GET"})
     *
     * @param ApiRequest $apiRequest
     *
     * @return DirectGraphResponse
     */
    public function getRelationTypes(
        ApiRequest $apiRequest
    ): DirectGraphResponse {
        return new DirectGraphResponse(
            RelationType::relationtypes(),
            $apiRequest->getFormat()
        );
    }
}
