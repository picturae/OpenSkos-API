<?php

declare(strict_types=1);

namespace App\OpenSkos\User\Controller;

use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Label\LabelRepository;
use App\OpenSkos\SkosResourceRepository;
use App\OpenSkos\User\User;
use App\Ontology\DcTerms;
use App\Ontology\Foaf;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\VCard;
use App\Rdf\Iri;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\OpenSkos\User\UserRepository;

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
     * @Route(path="/users.{format?}", methods={"GET"})
     *
     * @param UserRepository $repository
     * @param Connection     $connection
     * @param ApiRequest     $apiRequest
     *
     * @return ListResponse
     */
    public function geAllUsers(
        UserRepository $repository,
        Connection $connection,
        ApiRequest $apiRequest
    ): ListResponse {
        $nullResponse = new ListResponse(
            [], 0,
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );

        $auth = $apiRequest->getAuthentication();
        if (is_null($auth)) {
            return $nullResponse;
        }

        if ($auth->isAdministrator()) {
            $users = $repository->all(
                $apiRequest->getOffset(),
                $apiRequest->getLimit(),
            );
        } elseif ($auth->isAuthenticated()) {
            // Authenticated = only fetch yourself
            $user = $auth->getUser();
            if (is_null($user)) {
                return $nullResponse;
            }

            $uri = $user->getUri();
            if (is_null($uri)) {
                return $nullResponse;
            }

            $users = [
                $repository->get(new Iri($uri)),
            ];
        } else {
            $users = [];
        }

        return new ListResponse(
            $users,
            count($users),
            $apiRequest->getOffset(),
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/user/{id}.{format?}", methods={"GET"})
     *
     * @param string          $id
     * @param UserRepository  $repository
     * @param Connection      $connection
     * @param ApiRequest      $apiRequest
     * @param LabelRepository $labelRepository
     *
     * @return ScalarResponse
     */
    public function getOneUser(
        string $id,
        UserRepository $repository,
        Connection $connection,
        ApiRequest $apiRequest,
        LabelRepository $labelRepository
    ): ScalarResponse {
        \EasyRdf_Namespace::set('dcterms', DcTerms::NAME_SPACE);
        \EasyRdf_Namespace::set('foaf', Foaf::NAME_SPACE);
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);
        \EasyRdf_Namespace::set('rdf', Rdf::NAME_SPACE);
        \EasyRdf_Namespace::set('vcard', VCard::NAME_SPACE);

        if (array_key_exists('USER_IRI_PREFIX', $_ENV)) {
            if ('http' !== substr($id, 0, 4)) {
                $id = $_ENV['USER_IRI_PREFIX'].$id;
            }
        }
        $user = $repository->get(new Iri($id));

        if (null === $user) {
            throw new NotFoundHttpException("The user $id could not be retreived.");
        }

        return new ScalarResponse($user, $apiRequest->getFormat());
    }
}
