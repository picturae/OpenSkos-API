<?php

declare(strict_types=1);

namespace App\OpenSkos\ConceptScheme\Controller;

use App\OpenSkos\Filters\FilterProcessor;
use App\Ontology\OpenSkos;
use App\OpenSkos\ConceptScheme\ConceptSchemeRepository;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ConceptScheme
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
     * @Route(path="/conceptschemes", methods={"GET"})
     *
     * @param ApiRequest              $apiRequest
     * @param ConceptSchemeRepository $repository
     * @param FilterProcessor         $filterProcessor
     *
     * @return ListResponse
     */
    public function conceptschemes(
        ApiRequest $apiRequest,
        ConceptSchemeRepository $repository,
        FilterProcessor $filterProcessor
    ): ListResponse {
        $param_institutions = $apiRequest->getInstitutions();
        $institutions_filter = $filterProcessor->buildInstitutionFilters($param_institutions);

        if ($filterProcessor->hasPublisher($institutions_filter)) {
            throw new BadRequestHttpException('The search by Publisher URI for institutions could not be retrieved (Predicate is not used in Jena Store for Concept Schemes).');
        }

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

        $conceptschemes = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter);

        return new ListResponse(
            $conceptschemes,
            count($conceptschemes),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/conceptscheme/{id}", methods={"GET"})
     *
     * @param InternalResourceId      $id
     * @param ApiRequest              $apiRequest
     * @param ConceptSchemeRepository $repository
     *
     * @return ScalarResponse
     */
    public function conceptscheme(
        InternalResourceId $id,
        ApiRequest $apiRequest,
        ConceptSchemeRepository $repository
    ): ScalarResponse {
        $conceptscheme = $repository->findOneBy(
            new Iri(OpenSkos::UUID),
            $id
        );

        if (null === $conceptscheme) {
            throw new NotFoundHttpException("The conceptscheme $id could not be retreived.");
        }

        return new ScalarResponse($conceptscheme, $apiRequest->getFormat());
    }
}
