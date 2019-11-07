<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution\Controller;

use App\Ontology\Context;
use App\Ontology\OpenSkos;
use App\Ontology\Org;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Institution\InstitutionRepository;
use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class InstitutionController
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
     * @Route(path="/institutions.{format?}", methods={"GET"})
     */
    public function getInstitutions(
        ApiRequest $apiRequest,
        InstitutionRepository $repository
    ): ListResponse {
        $param_institutions = $apiRequest->getInstitutions();
        if (isset($param_institutions) && 0 !== count($param_institutions)) {
            throw new BadRequestHttpException('Institutions filter is not applicable here.');
        }

        /* According to the specs, throw a 400 when asked for sets */
        $param_sets = $apiRequest->getSets();
        if (isset($param_sets) && 0 !== count($param_sets)) {
            throw new BadRequestHttpException('Sets filter is not applicable here.');
        }

        $institutions = $repository->all($apiRequest->getOffset(), $apiRequest->getLimit());

        return new ListResponse(
            $institutions,
            count($institutions),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/institution/{id}.{format?}", methods={"GET"})
     */
    public function getInstitution(
        InternalResourceId $id,
        ApiRequest $apiRequest,
        InstitutionRepository $repository
    ): ScalarResponse {
        $institution = $repository->findOneBy(
            new Iri(OpenSkos::CODE),
            $id
        );

        if (null === $institution) {
            throw new NotFoundHttpException("The institution $id could not be retreived.");
        }

        return new ScalarResponse($institution, $apiRequest->getFormat());
    }

    /**
     * @Route(path="/institutions.{format?}", methods={"POST"})
     */
    public function postInstitution(
        ApiRequest $apiRequest,
        InstitutionRepository $repository
    ): ListResponse {
        Context::setupEasyRdf();

        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();

        // Check if valid data was given
        $graph = $apiRequest->getGraph();
        if (!count($graph->resources())) {
            throw new BadRequestHttpException('Request body was empty or corrupt');
        }

        // Pre-build checking params
        $shortOrg = implode(':', Context::decodeUri(Org::FORMAL_ORGANIZATION) ?? []);
        if (!strlen($shortOrg)) {
            throw new \Exception('Could not shorten org:FormalOrganization');
        }

        // Validate types
        $resources = $graph->resources();
        foreach ($resources as $resource) {
            // Ignore type resource
            if (Org::FORMAL_ORGANIZATION === $resource->getUri()) {
                continue;
            }

            // Pre-built checking vars
            $types = $resource->types();
            foreach ($types as $index => $type) {
                $types[$index] = implode(':', Context::decodeUri($type) ?? []);
            }

            // No org = invalid object
            if (!in_array($shortOrg, $types, true)) {
                throw new BadRequestHttpException('A non-institution resource was given');
            }
        }

        // Prevent insertion if one already exists
        $institutions = $graph->allOfType(Org::FORMAL_ORGANIZATION);
        foreach ($institutions as $institution) {
            // Find it by iri
            $uri = $institution->getUri();
            $found = $repository->findByIri(new Iri($uri));
            if (!is_null($found)) {
                throw new ConflictHttpException('The resource already exists');
            }
        }

        // Ensure all institutions have a UUID
        foreach ($institutions as $institution) {
            $uuid = $institution->getLiteral('openskos:uuid');
            if (is_null($uuid)) {
                $institution->addLiteral('openskos:uuid', @array_pop(explode('/', $institution->getUri())));
            }
        }

        // Insert
        $response = $repository->insertTriples($graph->serialise('ntriples'));

        // Build response
        foreach ($institutions as $index => $institution) {
            $institutions[$index] = $repository->findOneBy(
                new Iri(OpenSkos::UUID),
                new InternalResourceId($institution->getLiteral('openskos:uuid')->getValue())
            );
        }

        // Return re-fetched institutions
        return new ListResponse(
            $institutions,
            count($institutions),
            0,
            $apiRequest->getFormat()
        );
    }
}
