<?php

declare(strict_types=1);

namespace App\Healthcheck;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PingController extends AbstractController
{
    /**
     * @Route(path="/ping", methods={"GET"})
     */
    public function ping(): Response
    {
        return new Response('Hello OpenSkos world!');
    }
}
