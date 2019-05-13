<?php

declare(strict_types=1);

namespace App\Institution\Controller;

use App\Rest\ListResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class Institution
{
    /**
     * @Route(path="/institutions", methods={"GET"})
     */
    public function institutions() : JsonResponse
    {
        $list = new ListResponse([['name' => 'Test']], 10, 0);
        return new JsonResponse($list->toArray());
    }
}
