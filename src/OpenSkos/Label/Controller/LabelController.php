<?php

declare(strict_types=1);

namespace App\OpenSkos\Label\Controller;

use App\Annotation\Error;
use App\Annotation\OA;
use App\Exception\ApiException;
use App\OpenSkos\ApiFilter;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Filters\FilterProcessor;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Label\Label;
use App\OpenSkos\Label\LabelRepository;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class LabelController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @Route(path="/labels.{format?}", methods={"GET"})
     *
     * @OA\Summary("Fetch a list of all (filtered) labels")
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
     *       items=@OA\Schema\ObjectLiteral(class=Label::class),
     *     ),
     *   }),
     * )
     */
    public function getLabels(
        ApiRequest $apiRequest,
        ApiFilter $apiFilter,
        LabelRepository $repository,
        FilterProcessor $filterProcessor
    ): ListResponse {
        $full_filter = $apiFilter->buildFilters();
        $labels      = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter);

        return new ListResponse(
            $labels,
            count($labels),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * TODO: In OpenSkos2, UUID needs to be added to labels
     * TODO: Fetch by UUID instead of regex.
     *
     * @Route(path="/label/{id}.{format?}", methods={"GET"})
     *
     * @OA\Summary("Fetch a single label using it's identifier")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="id",
     *     in="path",
     *     example="1911",
     *   ),
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
     *       items=@OA\Schema\ObjectLiteral(class=Label::class),
     *     ),
     *   }),
     * )
     *
     * @Error(code="labelcontroller-getone-not-found",
     *        status=404,
     *        description="The requested label could not be retreived",
     *        fields={"id"}
     * )
     */
    public function getLabel(
       InternalResourceId $id,
       ApiRequest $apiRequest,
       LabelRepository $repository
    ): ScalarResponse {
        $label = $repository->getOneWithoutUuid($id);

        if (null === $label) {
            throw new ApiException('labelcontroller-getone-not-found', [
                'id' => $id,
            ]);
        }

        return new ScalarResponse($label, $apiRequest->getFormat());
    }

    /**
     * @Route(path="/label.{format?}", methods={"GET"})
     *
     * @OA\Summary("Retreive a label by foreign URI")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     *   @OA\Schema\StringLiteral(
     *     name="uri",
     *     in="query",
     *     example="http://openskos.org/pic/1911",
     *     required=true,
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Label::class),
     *     ),
     *   }),
     * )
     *
     * @Error(code="labelcontroller-getonebyuri-param-uri-missing",
     *        status=400,
     *        description="No uri was given"
     * )
     * @Error(code="labelcontroller-getonebyuri-not-found",
     *        status=404,
     *        description="The requested label could not be retreived",
     *        fields={"uri"}
     * )
     */
    public function getLabelByUri(
        ApiRequest $apiRequest,
        LabelRepository $repository
    ): ScalarResponse {
        $uri = $apiRequest->getParameter('uri', null);
        if (is_null($uri)) {
            throw new ApiException('labelcontroller-getonebyuri-param-uri-missing');
        }

        $iri   = new Iri($uri);
        $label = $repository->findByIri($iri);
        if (is_null($label)) {
            throw new ApiException('labelcontroller-getonebyuri-not-found', [
                'uri' => $uri,
            ]);
        }

        return new ScalarResponse($label, $apiRequest->getFormat());
    }
}
