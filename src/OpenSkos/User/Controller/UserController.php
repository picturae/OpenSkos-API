<?php

declare(strict_types=1);

namespace App\OpenSkos\User\Controller;

use App\Annotation\Error;
use App\Annotation\OA;
use App\Exception\ApiException;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Label\LabelRepository;
use App\OpenSkos\SkosResourceRepository;
use App\OpenSkos\User\User;
use App\OpenSkos\User\UserRepository;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
use Doctrine\DBAL\Driver\Connection;
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
     * @OA\Summary("Fetch a list of all (filtered) users")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=User::class),
     *     ),
     *   }),
     * )
     * @OA\Response(
     *   code="403",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\ObjectLiteral(class=Error::class),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @Error("user-getall-permission-denied-missing-user",
     *        status=403,
     *        description="The authenticated user could not be loaded"
     * )
     * @Error("user-getall-permission-denied-missing-user-uri",
     *        status=403,
     *        description="The uri for the authenticated user could not be loaded"
     * )
     */
    public function geAllUsers(
        UserRepository $repository,
        Connection $connection,
        ApiRequest $apiRequest
    ): ListResponse {
        // Not authenticated = no data
        $auth = $apiRequest->getAuthentication();
        $auth->requireAuthenticated();

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
                throw new ApiException('user-getall-permission-denied-missing-user');
            }

            // If we didn't find a uri, we can't fully  fetch our user
            $uri = $authenticatedUser->getUri();
            if (is_null($uri)) {
                throw new ApiException('user-getall-permission-denied-missing-user-uri');
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
     * @OA\Summary("Fetch a single user using it's identifier")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="id",
     *     in="path",
     *     example="1911",
     *   ),
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=User::class),
     *     ),
     *   }),
     * )
     * @OA\Response(
     *   code="403",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\ObjectLiteral(class=Error::class),
     *   }),
     * )
     * @OA\Response(
     *   code="404",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\ObjectLiteral(class=Error::class),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @Error(code="user-getone-permission-denied-missing-user",
     *        status=403,
     *        description="The authenticated user could not be loaded"
     * )
     * @Error(code="user-getone-permission-denied-missing-user-uri",
     *        status=403,
     *        description="The uri for the authenticated user could not be loaded"
     * )
     * @Error(code="user-getone-permission-denied-missing-role-administrator",
     *        status=403,
     *        description="The requested action requires the 'administrator' role while the authenticated user does not posses it"
     * )
     * @Error(code="user-getone-not-found-user",
     *        status=404,
     *        description="The requested user could not be found"
     * )
     */
    public function getOneUser(
        string $id,
        UserRepository $repository,
        Connection $connection,
        ApiRequest $apiRequest,
        LabelRepository $labelRepository
    ): ScalarResponse {
        // Not authenticated = no data
        $auth = $apiRequest->getAuthentication();
        $auth->requireAuthenticated();

        // Prepend known user prefix if it doesn't start with 'http'
        if (array_key_exists('USER_IRI_PREFIX', $_ENV)) {
            if ('http' !== substr($id, 0, 4)) {
                $id = $_ENV['USER_IRI_PREFIX'].$id;
            }
        }

        // An administrator is allowed to see anyone
        if ($auth->isAdministrator()) {
            $user = $repository->get(new Iri($id));
        } else {
            // We must have a user (likely, but still needs to be checked)
            $authenticatedUser = $auth->getUser();
            if (is_null($authenticatedUser)) {
                throw new ApiException('user-getone-permission-denied-missing-user');
            }

            // If we didn't find a uri, we can't fully  fetch our user
            $uri = $authenticatedUser->getUri();
            if (is_null($uri)) {
                throw new ApiException('user-getone-permission-denied-missing-user-uri');
            }

            // Denied if the authenticated user is not fetching itself
            if ($uri !== $id) {
                throw new ApiException('user-getone-permission-denied-missing-role-administrator');
            }

            $user = $repository->get(new Iri($id));
        }

        if (null === $user) {
            throw new ApiException('user-getone-not-found-user');
        }

        return new ScalarResponse($user, $apiRequest->getFormat());
    }
}
