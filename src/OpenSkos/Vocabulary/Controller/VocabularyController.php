<?php

declare(strict_types=1);

namespace App\OpenSkos\Vocabulary\Controller;

use App\Annotation\OA;
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
     *
     * @OA\Summary("Describe the OpenSkos RDF vocabulary")
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
     *         @OA\Schema\StringLiteral(name="dc:title"           , description="Title of the described field"                                                         ),
     *         @OA\Schema\StringLiteral(name="dcterms:description", description="A human-readable description of what the described field contains or should represent"),
     *         @OA\Schema\StringLiteral(name="openskos:datatype"  , description="What type of data to expect in the described field"                                   ),
     *         @OA\Schema\StringLiteral(name="rdf:Property"       , description="Which fields to expect in a resource of the described class"                          ),
     *       }),
     *     ),
     *   }),
     * )
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
