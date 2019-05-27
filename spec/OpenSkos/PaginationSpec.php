<?php

namespace spec\App\OpenSkos;

use App\OpenSkos\InvalidPaginationLevel;
use PhpSpec\ObjectBehavior;

class PaginationSpec extends ObjectBehavior
{
    public function it_throws_invalid_pagination_level_exception()
    {
        $this->beConstructedWith(5);
        $this->shouldThrow(InvalidPaginationLevel::class)->duringInstantiation();

        $this->beConstructedWith(0);
        $this->shouldThrow(InvalidPaginationLevel::class)->duringInstantiation();
    }

    public function it_throws_exception_for_negative_paging()
    {
        $this->beConstructedWith(1, -1, -42);
        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }
}
