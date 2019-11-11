<?php

namespace App\Security;

use App\Annotation\Error;
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
        $knownData = [];

        // Detect & remove prefix
        preg_match('/^(bearer|basic) /i', $token, $prefix);
        $token = preg_replace('/^(bearer|basic) /i', '', $token);
        $knownData['apikey'] = $token;

        // Handle special prefixes
        if (count($prefix)) {
            $prefix = strtolower($prefix[1]);
            switch ($prefix) {
                case 'basic':
                    unset($knownData['apikey']);
                    $decoded = base64_decode($token, true);
                    $parts = explode(':', $decoded);
                    $knownData['email'] = trim(array_shift($parts));
                    $knownData['password'] = md5(trim(implode(':', $parts)));
                    break;
            }
        }

        // Fetch user
        $user = new User($knownData);
        $user->populate();

        // Only api users are allowed to authenticate through this method
        $userType = $user->getType();
        if (!in_array($userType, ['both', 'api'], true)) {
            return;
        }

        // Only api users having an apikey are allowed
        // No compare because it was already checked if given
        if (!$user->getApikey()) {
            return;
        }

        // Fetch the user's roles
        // Verifies if the client is logged in as well
        $roles = $user->getRole();
        if (!count($roles)) {
            return;
        }

        // This code running means the client is verified
        $this->authenticated = true;
        $this->roles = $roles;

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
     * @return string[]
     */
    public function getRoles(): array
    {
        if (!$this->isAuthenticated()) {
            return [];
        }

        return $this->roles;
    }

    public function isAdministrator(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        return in_array('administrator', $this->getRoles(), true);
    }

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
     * @Error(code="permission-denied-invalid-credentials",
     *        status=403,
     * )
     * @Error(code="permission-denied-missing-credentials",
     *        status=401,
     * )
     */
    public function requireAuthenticated(string $errorCodePrefix = ''): void
    {
        if (!$this->hasAuthenticationData()) {
            throw new ApiException($errorCodePrefix.'permission-denied-missing-credentials');
        }
        if (!$this->isAuthenticated()) {
            throw new ApiException($errorCodePrefix.'permission-denied-invalid-credentials');
        }
    }

    /**
     * @throws ApiException
     *
     * @Error(code="permission-denied-missing-role-administrator",
     *        status=403,
     * )
     */
    public function requireAdministrator(string $errorCodePrefix = ''): void
    {
        $this->requireAuthenticated($errorCodePrefix);
        if (!$this->isAdministrator()) {
            throw new ApiException($errorCodePrefix.'permission-denied-missing-role-administrator');
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
