<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept\Controller;

use App\OpenSkos\Filters\FilterProcessor;
use App\Ontology\OpenSkos;
use App\OpenSkos\Concept\ConceptRepository;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class Concept
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
     * @Route(path="/concepts", methods={"GET"})
     *
     * @param ApiRequest        $apiRequest
     * @param ConceptRepository $repository
     * @param FilterProcessor   $filterProcessor
     *
     * @return ListResponse
     */
    public function concepts(
        ApiRequest $apiRequest,
        ConceptRepository $repository,
        FilterProcessor $filterProcessor
    ): ListResponse {
        $param_institutions = $apiRequest->getInstitutions();
        $institutions_filter = $filterProcessor->buildInstitutionFilters($param_institutions);

        if ($filterProcessor->hasPublisher($institutions_filter)) {
            throw new BadRequestHttpException('The search by Publisher URI for institutions could not be retrieved (Predicate is not used in Jena Store for Concepts).');
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

        $concepts = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter);

        return new ListResponse(
            $concepts,
            count($concepts),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/concept/{id}", methods={"GET"})
     *
     * @param InternalResourceId $id
     * @param ApiRequest         $apiRequest
     * @param ConceptRepository  $repository
     *
     * @return ScalarResponse
     */
    public function concept(
        InternalResourceId $id,
        ApiRequest $apiRequest,
        ConceptRepository $repository
    ): ScalarResponse {
        $concept = $repository->findOneBy(
            new Iri(OpenSkos::UUID),
            $id
        );

        if (null === $concept) {
            throw new NotFoundHttpException("The concept $id could not be retreived.");
        }

        return new ScalarResponse($concept, $apiRequest->getFormat());
    }
}
