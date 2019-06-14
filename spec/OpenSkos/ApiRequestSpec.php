<?php

namespace spec\App\OpenSkos;

use App\OpenSkos\InvalidApiRequestLevel;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiRequestSpec extends ObjectBehavior
{
    public function it_throws_invalid_pagination_level_exception()
    {
        $this->beConstructedWith('json-ld', 5);
        $this->shouldThrow(InvalidApiRequestLevel::class)->duringInstantiation();

        $this->beConstructedWith('json-ld', 0);
        $this->shouldThrow(InvalidApiRequestLevel::class)->duringInstantiation();
    }

    public function it_throws_exception_for_negative_paging()
    {
        $this->beConstructedWith('json-ld', 1, -1, -42);
        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    public function it_throws_a_wobbly_with_invalid_formats()
    {
        $this->beConstructedWith('something-i-just-made-up');
        $this->shouldThrow(HttpException::class)->duringInstantiation();
    }
}
