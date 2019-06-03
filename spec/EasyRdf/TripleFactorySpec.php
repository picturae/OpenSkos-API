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

        $testData = $this::triplesFromGraph($graph);

        $testData->shouldBeTriples([
            new Triple(
                new Iri('http://tenant/0e2a9a87-ea19-4704-90e6-a75b3baba80a'),
                new Iri('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'),
                new Iri('http://www.w3.org/ns/org#FormalOrganization')
            ),
            new Triple(
                new Iri('http://tenant/0e2a9a87-ea19-4704-90e6-a75b3baba80a'),
                new Iri('http://openskos.org/xmlns#code'),
                new Literal('pic')
            ),
            new Triple(
                new Iri('http://tenant/0e2a9a87-ea19-4704-90e6-a75b3baba80a'),
                new Iri('http://www.w3.org/2004/02/skos/core#prefLabel'),
                new Literal('Doe, John', 'nl')
            ),
            new Triple(
                new Iri('http://tenant/0e2a9a87-ea19-4704-90e6-a75b3baba80a'),
                new Iri('http://openskos.org/xmlns#disableSearchInOtherTenants'),
                new Literal('false', null, Literal::TYPE_BOOL)
            ),
            new Triple(
                new Iri('http://tenant/0e2a9a87-ea19-4704-90e6-a75b3baba80a'),
                new Iri('http://purl.org/dc/terms/dateSubmitted'),
                new Literal('2019-02-05T15:25:05+00:00', null, Literal::TYPE_DATETIME)
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
