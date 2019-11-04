<?php

declare(strict_types=1);

namespace App\Rest\ArgumentResolver;

use App\OpenSkos\ApiFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class ApiFilterResolver implements ArgumentValueResolverInterface
{
    /**
     * Returns whether or not the given argument is supported by this resolver.
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return ApiFilter::class === $argument->getType();
    }

    /**
     * @return \Generator
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield new ApiFilter($request);
    }
}
