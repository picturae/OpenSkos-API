<?php

namespace spec\App\Annotation\Document;

use DocBlockReader\Reader;
use PhpSpec\ObjectBehavior;

class TableSpec extends ObjectBehavior
{
    public function it_knows_its_name()
    {
        $this->name()->shouldReturn('document-table');
    }

    public function it_has_the_annotation_annotation()
    {
        $reader = new Reader($this->getWrappedObject());
        if (true !== $reader->getParameter('Annotation')) {
            throw new \Exception('Missing the @Annotation parameter');
        }
    }
}
