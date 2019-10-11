<?php

namespace spec\App\OpenSkos\RelationType;

use PhpSpec\ObjectBehavior;

class RelationTypeSpec extends ObjectBehavior
{
    public function it_returns_an_easyrdf_graph()
    {
        self::relationTypes()->shouldBeAnInstanceOf('EasyRdf_Graph');
    }
}
