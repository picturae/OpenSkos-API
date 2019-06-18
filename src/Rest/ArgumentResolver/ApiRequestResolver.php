<?php

declare(strict_types=1);

namespace App\Rest\ArgumentResolver;

use App\OpenSkos\ApiRequest;
use App\OpenSkos\Exception\InvalidApiRequest;
use App\Rdf\Format\RdfFormat;
use App\Rdf\Format\RdfFormatFactory;
use App\Rdf\Format\UnknownFormatException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class ApiRequestResolver implements ArgumentValueResolverInterface
{
    /**
     * @var RdfFormatFactory
     */
    private $formatFactory;

    public function __construct(
        RdfFormatFactory $formatFactory
    ) {
        $this->formatFactory = $formatFactory;
    }

    /**
     * @param string|null $formatName
     *
     * @return RdfFormat|null
     *
     * @throws InvalidApiRequest
     */
    private function resolveFormat(?string $formatName): ?RdfFormat
    {
        if (null === $formatName) {
            return null;
        }

        try {
            return $this->formatFactory->createFromName($formatName);
        } catch (UnknownFormatException $e) {
            throw new InvalidApiRequest('Invalid Format', 0, $e);
        }
    }

    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return ApiRequest::class === $argument->getType();
    }

    /**
     * @param Request          $request
     * @param ArgumentMetadata $argument
     *
     * @return \Generator
     *
     * @throws InvalidApiRequest
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield new ApiRequest(
            $this->resolveFormat($request->query->get('format')),
            $request->query->getInt('level', 1),
            $request->query->getInt('limit', 100),
            $request->query->getInt('offset', 0)
        );
    }
}
