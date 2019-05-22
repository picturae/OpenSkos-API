<?php

namespace spec\App\EasyRdf;

use App\Rdf\Iri;
use App\Rdf\Literal;
use App\Rdf\Triple;
use EasyRdf_Graph;
use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;

class TripleFactorySpec extends ObjectBehavior
{
    public function it_can_transform_easy_rdf_graph_to_triples()
    {
        $graphString = file_get_contents(__DIR__.'/example.ttl');
        $graph = new EasyRdf_Graph();
        $graph->parse($graphString, 'turtle');

        $this::triplesFromGraph($graph)->shouldBeTriples([
            new Triple(
                new Iri('http://memorix.io/skos/#russian'),
                new Iri('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'),
                new Iri('http://www.w3.org/2004/02/skos/core#Concept')
            ),
            new Triple(
                new Iri('http://memorix.io/skos/#russian'),
                new Iri('http://www.w3.org/2004/02/skos/core#prefLabel'),
                new Literal('Russian')
            ),
            new Triple(
                new Iri('http://memorix.io/skos/#dutch'),
                new Iri('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'),
                new Iri('http://www.w3.org/2004/02/skos/core#Concept')
            ),
            new Triple(
                new Iri('http://memorix.io/skos/#dutch'),
                new Iri('http://www.w3.org/2004/02/skos/core#prefLabel'),
                new Literal('Dutch')
            ),
        ]);
    }

    public function getMatchers(): array
    {
        return [
            'beTriples' => function (array $actual, array $expected) {
                foreach ($actual as $i => $triple) {
                    $expTriple = (string) ($expected[$i] ?? '<empty>');
                    if ((string) $triple !== $expTriple) {
                        throw new FailureException(sprintf(
                            "Expected triple\n\t%s\nat index #%d but got\n\t%s",
                            $expTriple, $i, $triple
                        ));
                    }
                }

                return true;
            },
        ];
    }
}
