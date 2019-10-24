<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;

class Authentication
{
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
    }
}
