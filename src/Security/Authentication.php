<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;

class Authentication
{
    public function __construct(
        Request $request
    ) {
        $token = null;

        $user = new User([
            'apikey' => 'pizza',
        ]);

        /* var_dump($user); */
    }
}
