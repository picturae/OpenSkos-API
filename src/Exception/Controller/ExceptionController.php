<?php

declare(strict_types=1);

namespace App\Exception\Controller;

use App\Annotation\Error;
use App\Annotation\OA;
use App\OpenSkos\ApiRequest;
use App\Rest\DirectGraphResponse;
use EasyRdf_Graph as Graph;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ExceptionController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var array
     */
    private $knownErrors;

    /**
     * Role constructor.
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer  = $serializer;
        $this->knownErrors = json_decode(file_get_contents(__DIR__.'/../list.json'), true);
    }

    /**
     * @Route(path="/errors.{format?}", methods={"GET"})
     *
     * @OA\Summary("Retreive all registered errors")
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
     *         @OA\Schema\StringLiteral(name="openskos:errorCode" , description="Unique identifier for the error"),
     *         @OA\Schema\StringLiteral(name="dcterms:description", description="Human-readable description of the error"),
     *         @OA\Schema\StringLiteral(name="rdf:Property"       , description="Which extra properties a thrown error may carry"),
     *         @OA\Schema\StringLiteral(name="http:sc"            , description="The http status code to expect when this error is thrown"),
     *       }),
     *     ),
     *   }),
     * )
     */
    public function getAllErrors(
        ApiRequest $apiRequest
    ): DirectGraphResponse {
        $graph = new Graph();

        foreach ($this->knownErrors as $code => $error) {
            if (!$error) {
                continue;
            }
            $resource = $graph->resource('http://error/'.$code);
            $resource->setType('openskos:error');
            $resource->addLiteral('openskos:errorCode', $error['code']);
            $resource->addLiteral('http:sc', $error['status'] ?? 500);

            if (isset($error['description'])) {
                $resource->addLiteral('dcterms:description', $error['description']);
            }

            $error['fields'] = $error['fields'] ?? [];
            foreach ($error['fields'] as $field) {
                $resource->addLiteral('rdf:Property', $field);
            }
        }

        /* die(json_encode($this->knownErrors, JSON_PRETTY_PRINT)); */

        return new DirectGraphResponse(
            $graph,
            $apiRequest->getFormat()
        );
    }
}
