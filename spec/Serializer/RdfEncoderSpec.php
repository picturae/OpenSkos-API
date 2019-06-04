<?php

namespace spec\App\Serializer;

use App\Institution\Institution;
use App\Rdf\Iri;
use App\Serializer\RdfEncoder;
use App\Rest\ListResponse;
use PhpSpec\ObjectBehavior;
use App\EasyRdf\TripleFactory;
use EasyRdf_Graph;

class RdfEncoderSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(RdfEncoder::class);
    }

    public function it_can_serialise_strings()
    {
        $graphString = <<<GRAPH_STRING
<http://tenant/TestData> <http://openskos.org/xmlns#code> "pic".
GRAPH_STRING;
        $graph = new EasyRdf_Graph();
        $graph->parse($graphString, 'turtle');

        $testData = TripleFactory::triplesFromGraph($graph);

        $graphUri = $testData[0]->getSubject()->getUri();

        $institutions = [Institution::fromTriples(new Iri($graphUri), $testData)];
        $list = new ListResponse($institutions, count($testData), 0);

        $output = ($this->encode($list->getDocs(), 'rdf', []));

        $expectedOutput = <<<RDF_BLOCK
<?xml version="1.0" encoding="utf-8" ?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:openskos="http://openskos.org/xmlns#">

  <rdf:Description rdf:about="http://tenant/TestData">
    <rdf:type rdf:resource="http://www.w3.org/1999/02/22-rdf-syntax-ns#Description"/>
    <openskos:code>pic</openskos:code>
  </rdf:Description>

</rdf:RDF>

RDF_BLOCK;

        $output->shouldBe($expectedOutput);
    }

    /*
     *This will fail until we build concepts, which do support language independen strings. So I've left it out for now
    public function it_can_serialise_language_dependent_strings()
    {
        $graphString = <<<GRAPH_STRING
<http://tenant/TestData> <http://www.w3.org/2004/02/skos/core#prefLabel> "Doe, John"@nl .
GRAPH_STRING;
        $graph = new EasyRdf_Graph();
        $graph->parse($graphString, 'turtle');

        $testData = TripleFactory::triplesFromGraph($graph);

        $graphUri = $testData[0]->getSubject()->getUri();

        $institutions = [Institution::fromTriples(new Iri($graphUri), $testData)];
        $list = new ListResponse($institutions, count($testData), 0);

        $output = ($this->encode($list->getDocs(), 'rdf', []));

        $expectedOutput = <<<RDF_BLOCK
<?xml version="1.0" encoding="utf-8" ?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">

  <rdf:Description rdf:about="http://tenant/TestData">
    <rdf:type rdf:resource="http://www.w3.org/1999/02/22-rdf-syntax-ns#Description"/>
    <skos:prefLabel xml:lang="nl">Doe, John</skos:prefLabel>
  </rdf:Description>

</rdf:RDF>

RDF_BLOCK;

        $output->shouldBe($expectedOutput);
    }
    */

    public function it_can_serialise_resources()
    {
        $graphString = <<<GRAPH_STRING
<http://tenant/TestData> a <http://www.w3.org/ns/org#FormalOrganization> .
GRAPH_STRING;
        $graph = new EasyRdf_Graph();
        $graph->parse($graphString, 'turtle');

        $testData = TripleFactory::triplesFromGraph($graph);

        $graphUri = $testData[0]->getSubject()->getUri();

        $institutions = [Institution::fromTriples(new Iri($graphUri), $testData)];
        $list = new ListResponse($institutions, count($testData), 0);

        $output = ($this->encode($list->getDocs(), 'rdf', []));

        $expectedOutput = <<<RDF_BLOCK
<?xml version="1.0" encoding="utf-8" ?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">

  <rdf:Description rdf:about="http://tenant/TestData">
    <rdf:type rdf:resource="http://www.w3.org/1999/02/22-rdf-syntax-ns#Description"/>
    <rdf:type rdf:resource="http://www.w3.org/ns/org#FormalOrganization"/>
  </rdf:Description>

</rdf:RDF>

RDF_BLOCK;

        $output->shouldBe($expectedOutput);
    }

    public function it_can_serialise_booleans()
    {
        $graphString = <<<GRAPH_STRING
<http://tenant/TestData> <http://openskos.org/xmlns#disableSearchInOtherTenants> "false"^^<http://www.w3.org/2001/XMLSchema#bool> .
GRAPH_STRING;
        $graph = new EasyRdf_Graph();
        $graph->parse($graphString, 'turtle');

        $testData = TripleFactory::triplesFromGraph($graph);

        $graphUri = $testData[0]->getSubject()->getUri();

        $institutions = [Institution::fromTriples(new Iri($graphUri), $testData)];
        $list = new ListResponse($institutions, count($testData), 0);

        $output = ($this->encode($list->getDocs(), 'rdf', []));

        $expectedOutput = <<<RDF_BLOCK
<?xml version="1.0" encoding="utf-8" ?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:openskos="http://openskos.org/xmlns#">

  <rdf:Description rdf:about="http://tenant/TestData">
    <rdf:type rdf:resource="http://www.w3.org/1999/02/22-rdf-syntax-ns#Description"/>
    <openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#boolean">false</openskos:disableSearchInOtherTenants>
  </rdf:Description>

</rdf:RDF>

RDF_BLOCK;

        $output->shouldBe($expectedOutput);
    }
}
