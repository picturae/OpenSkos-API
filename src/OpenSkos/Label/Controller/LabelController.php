<?php

declare(strict_types=1);

namespace App\OpenSkos\Label\Controller;

use App\OpenSkos\Filters\FilterProcessor;
/* use App\Ontology\OpenSkos; */
/* use App\OpenSkos\ConceptScheme\ConceptSchemeRepository; */
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Label\LabelRepository;
/* use App\OpenSkos\InternalResourceId; */
/* use App\Rdf\Iri; */
use App\Rest\ListResponse;
/* use App\Rest\ScalarResponse; */
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
/* use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; */
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
     * @param ApiRequest      $apiRequest
     * @param LabelRepository $repository
     * @param FilterProcessor $filterProcessor
     *
     * @return ListResponse
     */
    public function getLabels(
        ApiRequest $apiRequest,
        LabelRepository $repository,
        FilterProcessor $filterProcessor
    ): ListResponse {
        $param_institutions = $apiRequest->getInstitutions();
        $institutions_filter = $filterProcessor->buildInstitutionFilters($param_institutions);

        $param_sets = $apiRequest->getSets();
        $sets_filter = $filterProcessor->buildSetFilters($param_sets);
        $param_profile = $apiRequest->getSearchProfile();
        $full_filter = array_merge($institutions_filter, $sets_filter);

        if ($param_profile) {
            if (0 !== count($full_filter)) {
                throw new BadRequestHttpException('Search profile filters cannot be combined with other filters (possible conflicts).');
            }
            $to_apply = [FilterProcessor::ENTITY_INSTITUTION => true, FilterProcessor::ENTITY_SET => true];
            $full_filter = $filterProcessor->retrieveSearchProfile($param_profile, $to_apply);
        }

        $labels = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter);

        return new ListResponse(
            $labels,
            count($labels),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /*/***/
    /* * @Route(path="/conceptscheme/{id}.{format?}", methods={"GET"})*/
    /* **/
    /* * @param InternalResourceId      $id*/
    /* * @param ApiRequest              $apiRequest*/
    /* * @param ConceptSchemeRepository $repository*/
    /* **/
    /* * @return ScalarResponse*/
    /* */
    /*public function conceptscheme(*/
    /*    InternalResourceId $id,*/
    /*    ApiRequest $apiRequest,*/
    /*    ConceptSchemeRepository $repository*/
    /*): ScalarResponse {*/
    /*    $conceptscheme = $repository->findOneBy(*/
    /*        new Iri(OpenSkos::UUID),*/
    /*        $id*/
    /*    );*/

        /* if (null === $conceptscheme) { */
        /*     throw new NotFoundHttpException("The conceptscheme $id could not be retreived."); */
        /* } */

        /* return new ScalarResponse($conceptscheme, $apiRequest->getFormat()); */
    /* } */
}
