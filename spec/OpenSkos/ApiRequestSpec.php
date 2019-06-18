<?php

namespace spec\App\OpenSkos;

use App\OpenSkos\Exception\InvalidApiRequestLevel;
use App\Rdf\Format\RdfFormat;
use PhpSpec\ObjectBehavior;

class ApiRequestSpec extends ObjectBehavior
{
    public function it_throws_invalid_pagination_level_exception(
        RdfFormat $format
    ) {
        $this->beConstructedWith($format, 5);
        $this->shouldThrow(InvalidApiRequestLevel::class)->duringInstantiation();

        $this->beConstructedWith($format, 0);
        $this->shouldThrow(InvalidApiRequestLevel::class)->duringInstantiation();
    }

    public function it_throws_exception_for_negative_paging(
        RdfFormat $format
    ) {
        $this->beConstructedWith($format, 1, -1, -42);
        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }
}
