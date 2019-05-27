<?php

namespace spec\App\OpenSkos;

use App\OpenSkos\InvalidApiRequestLevel;
use PhpSpec\ObjectBehavior;

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
}
