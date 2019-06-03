<?php

namespace spec\App\Serializer;

use App\Serializer\RdfTestEncoder;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RdfTestEncoderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(RdfTestEncoder::class);
    }
}
