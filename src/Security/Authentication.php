<?php

namespace App\Security;

use App\Annotation\Error;
use App\Annotation\ErrorInherit;
use App\Entity\User;
use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\Request;

class Authentication
{
    /**
     * @var bool
     */
    protected $authenticated = false;

    /**
     * @var bool
     */
    protected $hasAuthenticationData = false;

    /**
     * @var string[]
     */
    protected $roles;

    /**
     * @var User|null
     */
    protected $user = null;

    /**
     * @ErrorInherit(class=User::class, method="__construct")
     * @ErrorInherit(class=User::class, method="getApikey"  )
     * @ErrorInherit(class=User::class, method="getRole"    )
     * @ErrorInherit(class=User::class, method="getType"    )
     * @ErrorInherit(class=User::class, method="populate"   )
     * @ErrorInherit(class=User::class, method="setPassword")
     */
    public function __construct(
        Request $request = null
    ) {
        if (is_null($request)) {
            return;
        }

        // Fetch bare token
        $token = null;
        $token = $token ?? $request->query->get('token');
        $token = $token ?? $request->headers->get('authorization');
        if (!is_string($token)) {
            return;
        }

        // The data we'll insert into the user
        $this->hasAuthenticationData = true;
        $knownData                   = [];

        // Detect & remove prefix
        preg_match('/^(bearer|basic) /i', $token, $prefix);
        $token               = preg_replace('/^(bearer|basic) /i', '', $token);
        $knownData['apikey'] = $token;

        // Normalize prefix
        if (count($prefix)) {
            $prefix = strtolower($prefix[1]);
        } else {
            $prefix = 'bearer';
        }

        // Explode 'basic' authentication
        if ('basic' == $prefix) {
            unset($knownData['apikey']);
            $decoded               = base64_decode($token, true);
            $parts                 = explode(':', $decoded);
            $knownData['email']    = trim(array_shift($parts));
            $knownData['password'] = md5(trim(implode(':', $parts)));
        }

        // Fetch user
        $user = new User($knownData);
        $user->populate();

        // Only api users are allowed to authenticate through this method
        $userType = $user->getType();
        if (!in_array($userType, ['both', 'api'], true)) {
            return;
        }

        // Validate api key
        if ('bearer' == $prefix) {
            $apiKey = $user->getApiKey();
            if ($apiKey !== $token) {
                return;
            }
        }

        // Fetch the user's roles
        // Verifies if the client is logged in as well
        $roles = $user->getRole();
        if (!count($roles)) {
            return;
        }

        // This code running means the client is verified
        $this->authenticated = true;
        $this->roles         = $roles;

        // Remove the password from the user
        $user->setPassword(null);
        $this->user = $user;
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    public function hasAuthenticationData(): bool
    {
        return $this->hasAuthenticationData;
    }

    /**
     * Available roles:
     *   - root
     *   - administrator
     *   - editor
     *   - user
     *   - guest.
     *
     * @ErrorInherit(class=Authentication::class, method="isAuthenticated")
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        if (!$this->isAuthenticated()) {
            return [];
        }

        return $this->roles;
    }

    /**
     * @ErrorInherit(class=Authentication::class, method="isAuthenticated")
     * @ErrorInherit(class=Authentication::class, method="getRoles"       )
     */
    public function isAdministrator(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        return in_array('administrator', $this->getRoles(), true);
    }

    /**
     * @ErrorInherit(class=Authentication::class, method="isAuthenticated")
     */
    public function getUser(): ?User
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $this->user;
    }

    /**
     * @throws ApiException
     *
     * @Error(code="authentication-permission-denied-invalid-credentials",
     *        status=403,
     *        description="Invalid credentials were given"
     * )
     * @Error(code="authentication-permission-denied-missing-credentials",
     *        status=401,
     *        description="No credentials were given"
     * )
     *
     * @ErrorInherit(class=Authentication::class, method="hasAuthenticationData")
     * @ErrorInherit(class=Authentication::class, method="isAuthenticated"      )
     */
    public function requireAuthenticated(string $errorCodePrefix = null): void
    {
        if (!$this->hasAuthenticationData()) {
            throw new ApiException(($errorCodePrefix ?? 'authentication').'-permission-denied-missing-credentials');
        }
        if (!$this->isAuthenticated()) {
            throw new ApiException(($errorCodePrefix ?? 'authentication').'-permission-denied-invalid-credentials');
        }
    }

    /**
     * @throws ApiException
     *
     * @Error(code="authentication-permission-denied-missing-role-administrator",
     *        status=403,
     *        description="The requested action requires the 'administrator' role while the authenticated user does not posses it"
     * )
     *
     * @ErrorInherit(class=Authentication::class, method="isAdministrator"     )
     * @ErrorInherit(class=Authentication::class, method="requireAuthenticated")
     */
    public function requireAdministrator(string $errorCodePrefix = null): void
    {
        $this->requireAuthenticated($errorCodePrefix ?? 'authentication');
        if (!$this->isAdministrator()) {
            throw new ApiException(($errorCodePrefix ?? 'authentication').'-permission-denied-missing-role-administrator');
        }
    }

    /*
     * TODO: role inheritance
     *   - is administrator granted if root is present?
     *   - is editor granted if administrator is present?
     *   - is user granted if editor is present?
     *   - is guest granted if user is present?
     */
}
