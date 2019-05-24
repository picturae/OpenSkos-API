<?php

namespace spec\App\Institution\Sparql;

use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\Skos;
use App\Rdf\Client;
use App\Rdf\Iri;
use App\Rdf\Literal;
use App\Rdf\Triple;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SparqlInstitutionRepositorySpec extends ObjectBehavior
{
    public function let(Client $rdfClient)
    {
        $this->beConstructedWith($rdfClient);
    }

    public function it_returns_all_insitution_from_sparql(Client $rdfClient)
    {
        $instA = new Iri('http://test/a');
        $instB = new Iri('http://test/b');

        $rdfClient->describe(Argument::any())->willReturn([
            new Triple($instA, new Iri(Rdf::TYPE), new Iri(Skos::CONCEPT)),
            new Triple($instB, new Iri(Rdf::TYPE), new Iri(Skos::CONCEPT)),
            new Triple($instA, new Iri(OpenSkos::CODE), new Literal('pic')),
            new Triple($instB, new Iri(OpenSkos::CODE), new Literal('test')),
            new Triple($instA, new Iri(OpenSkos::WEBPAGE), new Literal('http://picturae.com')),
        ]);

        $res = $this->all();
        $res->shouldHaveCount(2);
        $res[0]->getCode()->getValue()->shouldBe('pic');

        //TODO: Find a good matcher
    }
}
