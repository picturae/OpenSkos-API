<?php

declare(strict_types=1);

namespace App\OpenSkos\User\Controller;

use App\Ontology\Context;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Label\LabelRepository;
use App\OpenSkos\SkosResourceRepository;
use App\OpenSkos\User\User;
use App\OpenSkos\User\UserRepository;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @Route(path="/users.{format?}", methods={"GET"})
     *
     * @throws AccessDeniedHttpException
     */
    public function geAllUsers(
        UserRepository $repository,
        Connection $connection,
        ApiRequest $apiRequest
    ): ListResponse {
        Context::setupEasyRdf();

        // Not authenticated = no data
        $auth = $apiRequest->getAuthentication();
        if (is_null($auth)) {
            throw new AccessDeniedHttpException();
        }
        if (!$auth->isAuthenticated()) {
            throw new AccessDeniedHttpException();
        }

        if ($auth->isAdministrator()) {
            // Administrators are allowed to see all users
            $users = $repository->all(
                $apiRequest->getOffset(),
                $apiRequest->getLimit(),
            );
        } else {
            // We must have a user (likely, but still needs to be checked)
            $authenticatedUser = $auth->getUser();
            if (is_null($authenticatedUser)) {
                throw new AccessDeniedHttpException();
            }

            // If we didn't find a uri, we can't fully  fetch our user
            $uri = $authenticatedUser->getUri();
            if (is_null($uri)) {
                throw new AccessDeniedHttpException();
            }

            // Fetch an array of our authenticated user
            $users = [
                $repository->get(new Iri($uri)),
            ];
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
     * @throws AccessDeniedHttpException
     */
    public function getOneUser(
        string $id,
        UserRepository $repository,
        Connection $connection,
        ApiRequest $apiRequest,
        LabelRepository $labelRepository
    ): ScalarResponse {
        Context::setupEasyRdf();

        // Not authenticated = no data
        $auth = $apiRequest->getAuthentication();
        if (is_null($auth)) {
            throw new AccessDeniedHttpException();
        }
        if (!$auth->isAuthenticated()) {
            throw new AccessDeniedHttpException();
        }

        // Prepend known user prefix if it doesn't start with 'http'
        if (array_key_exists('USER_IRI_PREFIX', $_ENV)) {
            if ('http' !== substr($id, 0, 4)) {
                $id = $_ENV['USER_IRI_PREFIX'].$id;
            }
        }

        // An administrator is allowed to see anyone
        if (!$auth->isAdministrator()) {
            $user = $repository->get(new Iri($id));
        } else {
            // We must have a user (likely, but still needs to be checked)
            $authenticatedUser = $auth->getUser();
            if (is_null($authenticatedUser)) {
                throw new AccessDeniedHttpException();
            }

            // If we didn't find a uri, we can't fully  fetch our user
            $uri = $authenticatedUser->getUri();
            if (is_null($uri)) {
                throw new AccessDeniedHttpException();
            }

            // Denied if the authenticated user is not fetching itself
            if ($uri !== $id) {
                throw new AccessDeniedHttpException();
            }

            $user = $repository->get(new Iri($id));
        }

        if (null === $user) {
            throw new NotFoundHttpException("The user $id could not be retreived.");
        }

        return new ScalarResponse($user, $apiRequest->getFormat());
    }
}
