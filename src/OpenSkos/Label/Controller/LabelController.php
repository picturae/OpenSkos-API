<?php

declare(strict_types=1);

namespace App\OpenSkos\Label\Controller;

use App\OpenSkos\ApiFilter;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Filters\FilterProcessor;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Label\LabelRepository;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        $labels = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter);

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
     */
    public function getLabel(
       InternalResourceId $id,
       ApiRequest $apiRequest,
       LabelRepository $repository
    ): ScalarResponse {
        $label = $repository->getOneWithoutUuid($id);

        if (null === $label) {
            throw new NotFoundHttpException("The label $id could not be retreived.");
        }

        return new ScalarResponse($label, $apiRequest->getFormat());
    }
}
