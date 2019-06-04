<?php

namespace spec\App\Serializer;

use App\Serializer\RdfEncoder;
use PhpSpec\ObjectBehavior;

class RdfEncoderSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(RdfEncoder::class);
    }
}
