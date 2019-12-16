<?php

declare(strict_types=1);

namespace App\Rest\ArgumentResolver;

use App\Annotation\Error;
use App\Exception\ApiException;
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
     * @return \Generator
     *
     * @Error(code="internal-resource-id-resolver-unresolvable",
     *        status=500,
     *        description="The given value can not be resolved into InternalResourceId",
     *        fields={"value"}
     * )
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        $value = $request->attributes->get($argument->getName());
        if (null === $value || !is_string($value)) {
            throw new ApiException('internal-resource-id-resolver-unresolvable', [
                'value' => $value,
            ]);
        }

        yield new InternalResourceId($value);
    }
}
