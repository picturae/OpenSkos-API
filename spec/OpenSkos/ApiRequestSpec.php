<?php

namespace spec\App\OpenSkos;

use App\Exception\ApiException;
use App\Rdf\Format\RdfFormat;
use PhpSpec\ObjectBehavior;

class ApiRequestSpec extends ObjectBehavior
{
    public function it_throws_invalid_pagination_level_exception(
        RdfFormat $format
    ) {
        $this->beConstructedWith([], $format, 5);
        $this->shouldThrow(ApiException::class)->duringInstantiation();

        $this->beConstructedWith([], $format, 0);
        $this->shouldThrow(ApiException::class)->duringInstantiation();
    }

    public function it_throws_exception_for_negative_paging(
        RdfFormat $format
    ) {
        $this->beConstructedWith([], $format, 1, -1, -42);
        $this->shouldThrow(ApiException::class)->duringInstantiation();
    }
}
