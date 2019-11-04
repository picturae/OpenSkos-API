<?php

declare(strict_types=1);

namespace App\OpenSkos\Status\Controller;

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
     */
    public function status(
        ApiRequest $apiRequest
    ): DirectGraphResponse {
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);
        \EasyRdf_Namespace::set('rdf', Rdf::NAME_SPACE);

        // Define graph structure
        $graph = new \EasyRdf_Graph('openskos.org');
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
