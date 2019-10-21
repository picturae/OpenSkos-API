<?php

declare(strict_types=1);

namespace App\Healthcheck;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;

final class PingController extends AbstractController
{
    /**
     * @var KernelInterface
     */
    private $appKernel;

    /**
     * @param KernelInterface $appKernel
     */
    public function __construct(
        KernelInterface $appKernel = null
    ) {
        if (!is_null($appKernel)) {
            $this->appKernel = $appKernel;
        }
    }

    /**
     * @Route(path="/ping.{format?}", methods={"GET"})
     *
     * @param ApiRequest $apiRequest
     *
     * @return Response
     */
    public function ping(): Response
    {
        // Fetch composer data for the version
        $projectDir = $this->appKernel->getProjectDir();
        $composer = json_decode(file_get_contents(
            $projectDir.DIRECTORY_SEPARATOR.'composer.json'
        ));

        return new Response(json_encode([
            'status' => 'ok',
            'version' => $composer->version ?? 'n/a',
            'license' => $composer->license ?? 'proprietary',
            'copyright' => [
                'holder' => 'Picturae',
                'published' => 2019,
                'revised' => 2019,
            ],
        ]), 200, [
            'Content-Type' => 'application/json',
        ]);
    }
}
