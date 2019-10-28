<?php

declare(strict_types=1);

namespace App\OpenSkos\Vocabulary\Controller;

use App\Rest\DirectGraphResponse;
use App\Ontology\OpenSkos;
use App\OpenSkos\ApiRequest;
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
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @Route(path="/vocab.{format?}", methods={"GET"})
     *
     * @param ApiRequest $apiRequest
     *
     * @return DirectGraphResponse
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
