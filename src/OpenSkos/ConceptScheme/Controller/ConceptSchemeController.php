<?php

declare(strict_types=1);

namespace App\OpenSkos\ConceptScheme\Controller;

use App\Annotation\Error;
use App\Exception\ApiException;
use App\Ontology\OpenSkos;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\ConceptScheme\ConceptSchemeRepository;
use App\OpenSkos\Filters\FilterProcessor;
use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ConceptSchemeController
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
     * @Route(path="/conceptschemes.{format?}", methods={"GET"})
     *
     * @throws ApiException
     *
     * @Error(code="conceptscheme-getall-has-publisher-filter",
     *        status=400,
     *        description="The search by Publisher URI for institutions could not be retrieved (Predicate is not used in Jena Store for Concept Schemes)"
     * )
     */
    public function conceptschemes(
        ApiRequest $apiRequest,
        ConceptSchemeRepository $repository,
        FilterProcessor $filterProcessor
    ): ListResponse {
        $param_institutions = $apiRequest->getInstitutions();
        $institutions_filter = $filterProcessor->buildInstitutionFilters($param_institutions);

        if ($filterProcessor->hasPublisher($institutions_filter)) {
            throw new ApiException('conceptscheme-getall-has-publisher-filter');
        }

        $param_sets = $apiRequest->getSets();
        $sets_filter = $filterProcessor->buildSetFilters($param_sets);

        $full_filter = array_merge($institutions_filter, $sets_filter);

        $conceptschemes = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit(), $full_filter);

        return new ListResponse(
            $conceptschemes,
            count($conceptschemes),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/conceptscheme/{id}.{format?}", methods={"GET"})
     *
     * @throws ApiException
     *
     * @Error(code="conceptscheme-getone-not-found",
     *        status=404,
     *        description="The requested ConceptScheme could not be retreived",
     *        fields={"iri"}
     * )
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
            throw new ApiException('conceptscheme-getone-not-found', [
                'iri' => $id->id(),
            ]);
        }

        return new ScalarResponse($conceptscheme, $apiRequest->getFormat());
    }
}
