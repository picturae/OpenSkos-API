<?php

declare(strict_types=1);

namespace App\Healthcheck;

use App\Annotation\ErrorInherit;
use App\Annotation\OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route(path="/ping", methods={"GET"})
     *
     * @OA\Summary("Healthcheck and basic information")
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\StringLiteral(name="name"     , description="Name of the whole api being called", example="openskos-api"),
     *     @OA\Schema\StringLiteral(name="status"   , description="Status of the api"                 , example="ok"          ),
     *     @OA\Schema\StringLiteral(name="version"  , description="Version of the api"                , example="2.3"         ),
     *     @OA\Schema\StringLiteral(name="license"  , description="License the api operates under"    , example="proprietary" ),
     *     @OA\Schema\ObjectLiteral(name="copyright", description="Copyright information"             , properties={
     *       @OA\Schema\StringLiteral(name="holder"    , description="Holder of the copyright"             , example="Picturae"),
     *       @OA\Schema\IntegerLiteral(name="published", description="The year the copyright was published", example=2007),
     *       @OA\Schema\IntegerLiteral(name="revised"  , description="The year the copyright was revised"  , example=2020),
     *     }),
     *   }),
     * )
     *
     * @ErrorInherit(class=JenaRepository::class, method="__construct")
     * @ErrorInherit(class=JenaRepository::class, method="all"        )
     */
    public function ping(
        JenaRepository $jenaRepository
    ): Response {
        // Fetch composer data for the version
        $projectDir = $this->appKernel->getProjectDir();
        $composer   = json_decode(file_get_contents(
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
            'name'      => 'openskos-api',
            'status'    => $status,
            'version'   => $composer->version ?? 'n/a',
            'license'   => $composer->license ?? 'proprietary',
            'copyright' => [
                'holder'    => 'Picturae',
                'published' => 2007,
                'revised'   => 2020,
            ],
        ], JSON_PRETTY_PRINT), 200, [
            'Content-Type' => 'application/json',
        ]);
    }
}
