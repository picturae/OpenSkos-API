<?php

namespace spec\App\Annotation;

use DocBlockReader\Reader;
use PhpSpec\ObjectBehavior;

class ErrorSpec extends ObjectBehavior
{
    public function it_knows_its_name()
    {
        $this->name()->shouldReturn('error');
    }

    public function it_has_status_500_by_default()
    {
        $this->status->shouldBe(500);
    }

    public function it_has_the_annotation_annotation()
    {
        $reader = new Reader($this->getWrappedObject());
        if (true !== $reader->getParameter('Annotation')) {
            throw new \Exception('Missing the @Annotation parameter');
        }
    }
}
