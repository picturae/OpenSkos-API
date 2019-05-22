<?php

namespace spec\App\Institution;

use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\Skos;
use App\Rdf\Iri;
use App\Rdf\Literal;
use App\Rdf\Triple;
use PhpSpec\ObjectBehavior;

class InstitutionSpec extends ObjectBehavior
{
    public function it_can_be_created_from_triples()
    {
        $subjA = new Iri('http://test/a');
        $subjB = new Iri('http://test/B');

        $triples = [
            new Triple($subjA, new Iri(Rdf::TYPE), new Iri(Skos::CONCEPT)),
            new Triple($subjB, new Iri(Rdf::TYPE), new Iri(Skos::CONCEPT)),
            new Triple($subjA, new Iri(OpenSkos::CODE), new Literal('test')),
            new Triple($subjB, new Iri(OpenSkos::CODE), new Literal('pic')),
            new Triple($subjB, new Iri(OpenSkos::WEBPAGE), new Literal('http://picturae.com')),
        ];

        $this->beConstructedThrough('fromTriples', [$subjA, $triples]);

        $this->getSubject()->shouldBe($subjA);
        $this->getCode()->__toString()->shouldBe('test');
        $this->getWebsite()->shouldBeNull();

        $instB = $this::fromTriples($subjB, $triples);
        $instB->getSubject()->shouldBe($subjB);
        $instB->getWebsite()->__toString()->shouldBe('http://picturae.com');
    }
}
