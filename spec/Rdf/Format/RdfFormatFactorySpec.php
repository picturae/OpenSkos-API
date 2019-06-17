<?php

namespace spec\App\Rdf\Format;

use App\Rdf\Format\JsonLd;
use App\Rdf\Format\UnknownFormatException;
use PhpSpec\ObjectBehavior;

class RdfFormatFactorySpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedThrough('loadDefault');
    }

    public function it_can_create_format_by_name()
    {
        $this->createFromName('json-ld')->shouldBe(JsonLd::instance());
    }

    public function it_should_throw_an_exception_when_format_is_not_found()
    {
        $this->shouldThrow(UnknownFormatException::class)
            ->during('createFromName', ['unknown-format']);
    }
}
