<?php

namespace spec\App\Annotation\Document;

use DocBlockReader\Reader;
use PhpSpec\ObjectBehavior;

class TypeSpec extends ObjectBehavior
{
    public function it_knows_its_name()
    {
        $this->name()->shouldReturn('document-type');
    }

    public function it_has_the_annotation_annotation()
    {
        $reader = new Reader($this->getWrappedObject());
        if (true !== $reader->getParameter('Annotation')) {
            throw new \Exception('Missing the @Annotation parameter');
        }
    }
}
