<?php

declare(strict_types=1);

namespace App\OpenSkos\ConceptScheme\Controller;

use App\Annotation\Error;
use App\Entity\User;
use App\Exception\ApiException;
use App\Ontology\OpenSkos;
use App\OpenSkos\ApiFilter;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\ConceptScheme\ConceptSchemeRepository;
use App\OpenSkos\Filters\FilterProcessor;
use App\OpenSkos\Institution\InstitutionRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Set\SetRepository;
use App\Rdf\AbstractRdfDocument;
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
     */
    public function getConceptschemes(
        ApiFilter $apiFilter,
        ApiRequest $apiRequest,
        ConceptSchemeRepository $repository,
        FilterProcessor $filterProcessor
    ): ListResponse {
        $param_institutions  = $apiRequest->getInstitutions();

        // TODO: Don't use non-default filters anymore
        $apiFilter->addFilter('openskos:tenant', $apiRequest->getInstitutions());
        $apiFilter->addFilter('openskos:set', $apiRequest->getSets());
        $full_filter = $apiFilter->buildFilters();

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
    public function getConceptScheme(
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

    /**
     * @Route(path="/conceptschemes.{format?}", methods={"POST"})
     *
     * @throws ApiException
     *
     * @Error(code="conceptscheme-create-empty-or-corrupt-body",
     *        status=400,
     *        description="The body passed to this endpoint was either missing or corrupt"
     * )
     * @Error(code="conceptscheme-create-already-exists",
     *        status=409,
     *        description="A ConceptScheme with the given iri already exists",
     *        fields={"iri"}
     * )
     * @Error(code="conceptscheme-create-tenant-does-not-exist",
     *        status=400,
     *        description="The given tenant to create a ConceptScheme for does not exist",
     *        fields={"tenant"}
     * )
     * @Error(code="conceptscheme-create-set-does-not-exist",
     *        status=400,
     *        description="The given set to create a ConceptScheme for does not exist",
     *        fields={"set"}
     * )
     */
    public function postConceptScheme(
        ApiRequest $apiRequest,
        ConceptSchemeRepository $conceptSchemeRepository,
        SetRepository $setRepository,
        InstitutionRepository $institutionRepository
    ): ListResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();

        // Load data into sets
        $graph          = $apiRequest->getGraph();
        $conceptSchemes = $conceptSchemeRepository->fromGraph($graph);
        if (is_null($conceptSchemes)) {
            throw new ApiException('conceptscheme-create-empty-or-corrupt-body');
        }

        // Check if the resources already exist
        foreach ($conceptSchemes as $conceptScheme) {
            if ($conceptScheme->exists()) {
                throw new ApiException('conceptscheme-create-already-exists', [
                    'iri' => $conceptScheme->iri()->getUri(),
                ]);
            }
        }

        // Validate all given resources
        $errors = [];
        foreach ($conceptSchemes as $conceptScheme) {
            $errors = array_merge($errors, $conceptScheme->errors());
        }
        if (count($errors)) {
            foreach ($errors as $error) {
                throw new ApiException($error);
            }
        }

        // Ensure the tenants exist
        foreach ($conceptSchemes as $conceptScheme) {
            $tenantCode = $conceptScheme->getValue(OpenSkos::TENANT)->value();
            $tenant     = $institutionRepository->findOneBy(
                new Iri(OpenSkos::CODE),
                new InternalResourceId($tenantCode)
            );
            if (is_null($tenant)) {
                throw new ApiException('conceptscheme-create-tenant-does-not-exist', [
                    'tenant' => $tenantCode,
                ]);
            }
        }

        // Ensure the sets exist
        foreach ($conceptSchemes as $conceptScheme) {
            $setIri = $conceptScheme->getValue(OpenSkos::SET)->getUri();
            $set    = $setRepository->findByIri(new Iri($setIri));
            if (is_null($set)) {
                throw new ApiException('conceptscheme-create-set-does-not-exist', [
                    'set' => $setIri,
                ]);
            }
        }

        // Save all given conceptSchemes
        foreach ($conceptSchemes as $conceptScheme) {
            $errors = $conceptScheme->save();
            if ($errors) {
                throw new ApiException($errors[0]);
            }
        }

        return new ListResponse(
            $conceptSchemes,
            count($conceptSchemes),
            0,
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/conceptscheme/{id}.{format?}", methods={"DELETE"})
     *
     * @throws ApiException
     */
    public function deleteConceptScheme(
        InternalResourceId $id,
        ApiRequest $apiRequest,
        ConceptSchemeRepository $repository
    ): ScalarResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();

        // Fetch the set we're deleting
        /** @var AbstractRdfDocument $conceptScheme */
        $conceptScheme = $this->getConceptScheme($id, $apiRequest, $repository)->doc();

        $conceptScheme->delete();

        return new ScalarResponse(
            $conceptScheme,
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/conceptschemes.{format?}", methods={"PUT"})
     *
     * @Error(code="conceptscheme-update-empty-or-corrupt-body",
     *        status=400,
     *        description="The body passed to this endpoint was either missing or corrupt"
     * )
     * @Error(code="conceptscheme-update-does-not-exist",
     *        status=400,
     *        description="The set with the given iri does not exist",
     *        fields={"iri"}
     * )
     * @Error(code="conceptscheme-update-tenant-does-not-exist",
     *        status=400,
     *        description="The given tenant to update a conceptscheme for does not exist",
     *        fields={"tenant"}
     * )
     * @Error(code="conceptscheme-update-set-does-not-exist",
     *        status=400,
     *        description="The given set to update a conceptscheme for does not exist",
     *        fields={"set"}
     * )
     */
    public function putConceptScheme(
        ApiRequest $apiRequest,
        ConceptSchemeRepository $conceptSchemeRepository,
        SetRepository $setRepository,
        InstitutionRepository $institutionRepository
    ): ListResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();
        /** @var User $user */
        $user = $auth->getUser();

        // Load data into concept schemes
        $graph           = $apiRequest->getGraph();
        $conceptschemes  = $conceptSchemeRepository->fromGraph($graph);
        if (is_null($conceptschemes)) {
            throw new ApiException('conceptscheme-update-empty-or-corrupt-body');
        }

        // Validate all given resources
        $errors = [];
        foreach ($conceptschemes as $conceptscheme) {
            if (!$conceptscheme->exists()) {
                throw new ApiException('conceptscheme-update-does-not-exist', [
                    'iri' => $conceptscheme->iri()->getUri(),
                ]);
            }
            $errors = array_merge($errors, $conceptscheme->errors());
        }
        if (count($errors)) {
            foreach ($errors as $error) {
                throw new ApiException($error);
            }
        }

        // Ensure the tenants exist
        foreach ($conceptschemes as $conceptscheme) {
            $tenantCode = $conceptscheme->getValue(OpenSkos::TENANT)->value();
            $tenant     = $institutionRepository->findOneBy(
                new Iri(OpenSkos::CODE),
                new InternalResourceId($tenantCode)
            );
            if (is_null($tenant)) {
                throw new ApiException('conceptscheme-update-tenant-does-not-exist', [
                    'tenant' => $tenantCode,
                ]);
            }
        }

        // Ensure the sets exist
        foreach ($conceptschemes as $conceptscheme) {
            $setIri = $conceptscheme->getValue(OpenSkos::SET);
            $set    = $setRepository->findByIri($setIri);
            if (is_null($set)) {
                throw new ApiException('conceptscheme-update-set-does-not-exist', [
                    'set' => $setIri->getUri(),
                ]);
            }
        }

        // Rebuild all given ConceptSchemes
        $modifier = new Iri(OpenSkos::MODIFIED_BY);
        foreach ($conceptschemes as $conceptscheme) {
            $conceptscheme->setValue($modifier, $user->iri());
            $errors = $conceptscheme->update();
            if ($errors) {
                throw new ApiException($errors[0]);
            }
        }

        return new ListResponse(
            $conceptschemes,
            count($conceptschemes),
            0,
            $apiRequest->getFormat()
        );
    }
}
