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

        // Remove known prefixes
        $token = preg_replace('/^Bearer /', '', $token);

        // Fetch user
        $user = new User(['apikey' => $token]);
        $user->populate();

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
