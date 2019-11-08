<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution\Controller;

use App\Ontology\Context;
use App\Ontology\OpenSkos;
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

        // Load data into institutions
        $graph = $apiRequest->getGraph();
        $institutions = $repository->fromGraph($graph);
        if (is_null($institutions)) {
            throw new BadRequestHttpException('Request body was empty or corrupt');
        }

        // Validate all given resources
        $errors = [];
        foreach ($institutions as $institution) {
            $errors = array_merge($errors, $institution->errors());
        }
        if (count($errors)) {
            throw new \Exception(json_encode($errors));
        }

        // Check if the resources already exist
        foreach ($institutions as $institution) {
            if ($institution->exists()) {
                throw new ConflictHttpException('Resource [id] already exists');
            }
        }

        // TODO: $institutions[]->save()

        /* // Insert */
        /* $response = $repository->insertTriples($graph->serialise('ntriples')); */

        /* // Build response */
        /* foreach ($institutions as $index => $institution) { */
        /*     $institutions[$index] = $repository->findOneBy( */
        /*         new Iri(OpenSkos::UUID), */
        /*         new InternalResourceId($institution->getLiteral('openskos:uuid')->getValue()) */
        /*     ); */
        /* } */

        // Return re-fetched institutions
        return new ListResponse(
            $institutions,
            count($institutions),
            0,
            $apiRequest->getFormat()
        );
    }
}
