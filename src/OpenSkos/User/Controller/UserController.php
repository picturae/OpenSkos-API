<?php

declare(strict_types=1);

namespace App\OpenSkos\User\Controller;

use App\Rest\DirectGraphResponse;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Label\LabelRepository;
use App\OpenSkos\SkosResourceRepository;
use App\OpenSkos\User\UserRepository;
use App\Ontology\Foaf;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Rdf\Iri;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class UserController
{
    /**
     * @var SkosResourceRepository
     */
    private $repository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Role constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @Route(path="/users", methods={"GET"})
     *
     * @param UserRepository $repository
     * @param Connection $connection
     * @param ApiRequest $apiRequest
     *
     * @return ListResponse
     */
    public function geAlltUsers(
        UserRepository $repository,
        Connection $connection,
        ApiRequest $apiRequest
    ): ListResponse {
        \EasyRdf_Namespace::set('foaf', Foaf::NAME_SPACE);
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);
        \EasyRdf_Namespace::set('rdf', Rdf::NAME_SPACE);

        $users = $repository->all();

        return new ListResponse(
            $users,
            count($users),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/user/{id}", methods={"GET"})
     *
     * @param InternalResourceId $id
     * @param UserRepository $repository
     * @param Connection $connection
     * @param ApiRequest $apiRequest
     * @param LabelRepository $labelRepository
     *
     * @return ScalarResponse
     */
    public function getOneUser(
        InternalResourceId $id,
        UserRepository $repository,
        Connection $connection,
        ApiRequest $apiRequest,
        LabelRepository $labelRepository
    ): ScalarResponse {
        \EasyRdf_Namespace::set('foaf', Foaf::NAME_SPACE);
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);
        \EasyRdf_Namespace::set('rdf', Rdf::NAME_SPACE);

        $user = $repository->findOneBy(
            new Iri(OpenSkos::UUID),
            $id
        );

        if (null === $user) {
            throw new NotFoundHttpException("The user $id could not be retreived.");
        }
        if (2 === $apiRequest->getLevel()) {
            $user->loadFullXlLabels($labelRepository);
        }

        return new ScalarResponse($user, $apiRequest->getFormat());
    }
}
