<?php

namespace spec\App\Rest\ArgumentResolver;

use App\OpenSkos\ApiRequest;
use App\OpenSkos\Exception\InvalidApiRequest;
use App\Rdf\Format\JsonLd;
use App\Rdf\Format\RdfFormatFactory;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ApiRequestResolverSpec extends ObjectBehavior
{
    /**
     * @var RdfFormatFactory
     */
    private $formatFactory;

    public function __construct()
    {
        $this->formatFactory = RdfFormatFactory::loadDefault();
    }

    public function let()
    {
        $this->beConstructedWith($this->formatFactory);
    }

    public function it_resolves_pagination_from_symfony_request(
        ArgumentMetadata $argumentMetadata
    ) {
        $request = new Request(
            [
                'format' => 'json-ld',
                'limit' => '123',
                'offset' => '12',
                'level' => '1',
            ]
        );

        /** @var ApiRequest $apiRequest */
        $apiRequest = $this->resolve($request, $argumentMetadata)->current();

        $apiRequest->getFormat()->shouldBeAnInstanceOf(JsonLd::class);
        $apiRequest->getLevel()->shouldBe(1);
        $apiRequest->getLimit()->shouldBe(123);
        $apiRequest->getOffset()->shouldBe(12);
    }

    public function it_throws_an_exception_when_cant_resolve_format(
        ArgumentMetadata $argumentMetadata
    ) {
        $request = new Request(
            [
                'format' => 'unknown-format',
                'limit' => '123',
                'offset' => '12',
                'level' => '1',
            ]
        );

        $this->resolve($request, $argumentMetadata)
            ->shouldThrow(InvalidApiRequest::class)
            ->during('current');
    }
}
