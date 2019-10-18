<?php

declare(strict_types=1);

namespace App\OpenSkos\Role\Controller;

use App\Rest\DirectGraphResponse;
use App\OpenSkos\ApiRequest;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class Role
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
     * @Route(path="/roles.{format?}", methods={"GET"})
     *
     * @param ApiRequest $apiRequest
     *
     * @return DirectGraphResponse
     */
    public function role(
        ApiRequest $apiRequest
    ): DirectGraphResponse {
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);
        \EasyRdf_Namespace::set('rdf', Rdf::NAME_SPACE);

        // Define graph structure
        $graph = new \EasyRdf_Graph('openskos.org');
        $roles = $graph->resource('openskos:Role');
        $roles->setType('openskos:Role');

        // Define available roles
        $roles->addLiteral('rdf:value', 'root');
        $roles->addLiteral('rdf:value', 'administrator');
        $roles->addLiteral('rdf:value', 'editor');
        $roles->addLiteral('rdf:value', 'user');
        $roles->addLiteral('rdf:value', 'guest');

        return new DirectGraphResponse(
          $graph,
          $apiRequest->getFormat()
        );
    }
}
