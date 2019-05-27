<?php

namespace spec\App\Rest\ArgumentResolver;

use App\OpenSkos\Pagination;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class PaginationResolverSpec extends ObjectBehavior
{
    public function it_resolves_pagination_from_symfony_request(
        ArgumentMetadata $argumentMetadata
    ) {
        $request = new Request(
            [
                'limit' => '123',
                'offset' => '12',
                'level' => '1',
            ]
        );

        /** @var Pagination $pagination */
        $pagination = $this->resolve($request, $argumentMetadata)->current();

        $pagination->getLevel()->shouldBe(1);
        $pagination->getLimit()->shouldBe(123);
        $pagination->getOffset()->shouldBe(12);
    }

    public function it_returns_correct_default_values(
        ArgumentMetadata $argumentMetadata
    ) {
        $request = new Request();

        /** @var Pagination $pagination */
        $pagination = $this->resolve($request, $argumentMetadata)->current();

        $pagination->getLevel()->shouldBe(1);
        $pagination->getLimit()->shouldBe(100);
        $pagination->getOffset()->shouldBe(0);
    }
}
