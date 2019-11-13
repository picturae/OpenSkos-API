<?php

declare(strict_types=1);

namespace App\Exception\Controller;

use App\Ontology\Context;
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
        $this->serializer = $serializer;
        $this->knownErrors = json_decode(file_get_contents(__DIR__.'/../list.json'), true);
    }

    /**
     * @Route(path="/errors.{format?}", methods={"GET"})
     */
    public function getAllErrors(
        ApiRequest $apiRequest
    ): DirectGraphResponse {
        Context::setupEasyRdf();

        $graph = new Graph();

        foreach ($this->knownErrors as $code => $error) {
            if (!$error) {
                continue;
            }
            $resource = $graph->resource('http://error/'.$code);
            $resource->setType('openskos:error');
            $resource->addLiteral('http:sc', $error['status'] ?? 500);

            if (isset($error['description'])) {
                $resource->addLiteral('rdf:comment', $error['description']);
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
