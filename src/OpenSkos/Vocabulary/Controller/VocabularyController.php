<?php

declare(strict_types=1);

namespace App\OpenSkos\Vocabulary\Controller;

use App\Ontology\OpenSkos;
use App\OpenSkos\ApiRequest;
use App\Rest\DirectGraphResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class VocabularyController
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
     * @Route(path="/vocab.{format?}", methods={"GET"})
     */
    public function getRelationTypes(
        ApiRequest $apiRequest
    ): DirectGraphResponse {
        return new DirectGraphResponse(
            OpenSkos::vocabulary(),
            $apiRequest->getFormat()
        );
    }
}
