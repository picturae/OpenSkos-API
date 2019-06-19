<?php

namespace spec\App\Rdf\Sparql;

use App\Rdf\Iri;
use PhpSpec\ObjectBehavior;

class SparqlQuerySpec extends ObjectBehavior
{
    public function it_can_create_describe_all_of_type_query()
    {
        $this->beConstructedThrough('describeAllOfType',
            [
                new Iri('http://some-type'),
                200, // offset
                100,  // limit
            ]
        );

        $this->rawSparql()->shouldBe(
            'DESCRIBE ?x WHERE '
            .'{ ?x <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://some-type> } '
            .'LIMIT 100 OFFSET 200'
        );
    }
}
