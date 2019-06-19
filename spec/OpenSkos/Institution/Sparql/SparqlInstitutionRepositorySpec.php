<?php

namespace spec\App\OpenSkos\Institution\Sparql;

use App\OpenSkos\OpenSkosIriFactory;
use App\Rdf\Sparql\Client;
use App\Rdf\Iri;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SparqlInstitutionRepositorySpec extends ObjectBehavior
{
    private $iriFactory;

    public function let(
        Client $rdfClient
    ) {
        $this->iriFactory = new OpenSkosIriFactory('http://tenant');
        $this->beConstructedWith($rdfClient, $this->iriFactory);
    }

    public function it_will_return_null_if_no_one_triple_found_for_given_iri(Client $rdfClient)
    {
        $rdfClient->describe(Argument::any())->willReturn([]);
        $this->findByIri(new Iri('http://tenant/iri'))->shouldBeNull();
    }
}
