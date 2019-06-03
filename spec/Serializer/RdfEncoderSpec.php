<?php

namespace spec\App\Serializer;

use App\Institution\Institution;
use App\Rdf\Iri;
use App\Serializer\RdfEncoder;
use App\Rest\ListResponse;
use Symfony\Component\Serializer\Serializer;
use PhpSpec\ObjectBehavior;
use App\EasyRdf\TripleFactory;
use EasyRdf_Graph;

class RdfEncoderSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(RdfEncoder::class);
    }

    public function it_can_serialise_triple_stores()
    {
        $graphString = file_get_contents(__DIR__.'/../EasyRdf/example.ttl');
        $graph = new EasyRdf_Graph();
        $graph->parse($graphString, 'turtle');


        $testData = TripleFactory::triplesFromGraph($graph);

        $graphUri = $testData[0]->getSubject()->getUri();

        $institutions = Institution::fromTriples(new Iri($graphUri), $testData);
        $list = new ListResponse($institutions, count($testData), 0);

        //$res = $serializer->serialize($list->getDocs(), 'json', []);

        $output = $this->encode($list->getDocs(), 'rdf', []);
    }
}
