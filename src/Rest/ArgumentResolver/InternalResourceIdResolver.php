<?php

declare(strict_types=1);

namespace App\Rest\ArgumentResolver;

use App\OpenSkos\InternalResourceId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class InternalResourceIdResolver implements ArgumentValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return InternalResourceId::class === $argument->getType()
            && $request->attributes->has($argument->getName());
    }

    /**
     * @param Request          $request
     * @param ArgumentMetadata $argument
     *
     * @return \Generator
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        $value = $request->attributes->get($argument->getName());
        if (null === $value || !is_string($value)) {
            throw new \InvalidArgumentException("Can't resolve InternalResourceId");
        }

        yield new InternalResourceId($value);
    }
}
