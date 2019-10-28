<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class Authentication
{
    /**
     * @var bool
     */
    protected $authenticated = false;

    /**
     * @var string[]
     */
    protected $roles;

    /**
     * @var User|null
     */
    protected $user = null;

    public function __construct(
        Request $request
    ) {
        // Fetch bare token
        $token = null;
        $token = $token ?? $request->query->get('token');
        $token = $token ?? $request->headers->get('authorization');
        if (is_null($token)) {
            return;
        }

        // The data we'll insert into the user
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
                    $decoded = base64_decode($token, true);
                    $parts = explode(':', $decoded);
                    $knownData['email'] = trim(array_shift($parts));
                    $knownData['apikey'] = trim(implode(':', $parts));
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

    /**
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->authenticated;
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

    /**
     * @return bool
     */
    public function isAdministrator(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        return in_array('administrator', $this->getRoles(), true);
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $this->user;
    }

    /*
     * TODO: role inheritance
     *   - is administrator granted if root is present?
     *   - is editor granted if administrator is present?
     *   - is user granted if editor is present?
     *   - is guest granted if user is present?
     */
}