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
     * @param JenaRepository $jenaRepository
     *
     * @return Response
     */
    public function ping(
        JenaRepository $jenaRepository
    ): Response {
        // Fetch composer data for the version
        $projectDir = $this->appKernel->getProjectDir();
        $composer = json_decode(file_get_contents(
            $projectDir.DIRECTORY_SEPARATOR.'composer.json'
        ));

        // Define status checks
        // TODO: define these elsewhere
        /** @var callable[] $statusChecks */
        $statusChecks = [
            // Check if jena is up & usable
            function () use ($jenaRepository): bool {
                try {
                    $persons = $jenaRepository->all(0, 1);

                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            },

            // At least 1 user must exist in jena
            function () use ($jenaRepository): bool {
                try {
                    $persons = $jenaRepository->all(0, 1);

                    return 1 === count($persons);
                } catch (\Exception $e) {
                    return false;
                }
            },
        ];

        // Run all status checks
        $pass = 0;
        $fail = 0;
        foreach ($statusChecks as $statusCheck) {
            if (call_user_func($statusCheck)) {
                ++$pass;
            } else {
                ++$fail;
            }
        }

        // Define status
        if (0 === $fail) {
            $status = 'ok';
        } elseif (0 === $pass) {
            $status = 'down';
        } else {
            $status = 'partial';
        }

        return new Response(json_encode([
            'status' => $status,
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
