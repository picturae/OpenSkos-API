<?php

declare(strict_types=1);

namespace App\OpenSkos\Label\Controller;

use App\Annotation\Error;
use App\Exception\ApiException;
use App\OpenSkos\ApiFilter;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Filters\FilterProcessor;
use App\OpenSkos\InternalResourceId;
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
