<?php

declare(strict_types=1);

namespace App\OpenSkos\Role\Controller;

use App\Annotation\ErrorInherit;
use App\Annotation\OA;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\OpenSkos\ApiRequest;
use App\Rest\DirectGraphResponse;
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
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @Route(path="/roles.{format?}", methods={"GET"})
     *
     * @OA\Summary("Retreive all available user roles")
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
     *         @OA\Schema\StringLiteral(name="rdf:value", description="One of the allowed values for this openskos:role field"),
     *       }),
     *     ),
     *   }),
     * )
     *
     * @ErrorInherit(class=ApiRequest::class         , method="__construct")
     * @ErrorInherit(class=ApiRequest::class         , method="getFormat"  )
     * @ErrorInherit(class=DirectGraphResponse::class, method="__construct"  )
     */
    public function role(
        ApiRequest $apiRequest
    ): DirectGraphResponse {
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);
        \EasyRdf_Namespace::set('rdf', Rdf::NAME_SPACE);

        // Define graph structure
        $graph = new \EasyRdf_Graph('openskos.org');
        $roles = $graph->resource('openskos:role');
        $roles->setType('openskos:role');

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
