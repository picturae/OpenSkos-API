<?php

namespace spec\App\Serializer;

use App\Rdf\Format\RdfFormatFactory;
use App\Rdf\Iri;
use App\Rdf\Literal\BooleanLiteral;
use App\Rdf\Literal\DatetimeLiteral;
use App\Rdf\Literal\StringLiteral;
use App\Rdf\Triple;
use PhpSpec\ObjectBehavior;

class RdfEncoderSpec extends ObjectBehavior
{
    /**
     * @var Triple[]
     */
    private $triples;

    public function let()
    {
        $this->triples = [
            new Triple(
                new Iri('http://tenant/0e2a9a87-ea19-4704-90e6-a75b3baba80a'),
                new Iri('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'),
                new Iri('http://www.w3.org/ns/org#FormalOrganization')
            ),
            new Triple(
                new Iri('http://tenant/0e2a9a87-ea19-4704-90e6-a75b3baba80a'),
                new Iri('http://openskos.org/xmlns#code'),
                new StringLiteral('pic')
            ),
            new Triple(
                new Iri('http://tenant/0e2a9a87-ea19-4704-90e6-a75b3baba80a'),
                new Iri('http://www.w3.org/2004/02/skos/core#prefLabel'),
                new StringLiteral('Doe, John', 'nl')
            ),
            new Triple(
                new Iri('http://tenant/0e2a9a87-ea19-4704-90e6-a75b3baba80a'),
                new Iri('http://openskos.org/xmlns#disableSearchInOtherTenants'),
                new BooleanLiteral(false)
            ),
            new Triple(
                new Iri('http://tenant/0e2a9a87-ea19-4704-90e6-a75b3baba80a'),
                new Iri('http://purl.org/dc/terms/dateSubmitted'),
                new DatetimeLiteral(new \DateTime('2019-02-05T15:25:05+00:00'))
            ),
        ];
        $this->beConstructedWith(RdfFormatFactory::loadDefault());
    }

    public function it_can_serialise_to_jsonld()
    {
        $res = $this->encode($this->triples, 'json-ld');
        $res->shouldBe(file_get_contents(__DIR__.'/example.jsonld'));
    }

    public function it_can_serialise_to_turtle()
    {
        $res = $this->encode($this->triples, 'turtle');
        $res->shouldBe(file_get_contents(__DIR__.'/example.ttl'));
    }

    public function it_can_serialise_to_rdfxml()
    {
        $res = $this->encode($this->triples, 'rdfxml');
        $expected = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:org="http://www.w3.org/ns/org#"
         xmlns:openskos="http://openskos.org/xmlns#"
         xmlns:skos="http://www.w3.org/2004/02/skos/core#"
         xmlns:dc="http://purl.org/dc/terms/">

  <org:FormalOrganization rdf:about="http://tenant/0e2a9a87-ea19-4704-90e6-a75b3baba80a">
    <openskos:code>pic</openskos:code>
    <skos:prefLabel xml:lang="nl">Doe, John</skos:prefLabel>
    <openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#boolean">false</openskos:disableSearchInOtherTenants>
    <dc:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2019-02-05T15:25:05Z</dc:dateSubmitted>
  </org:FormalOrganization>

</rdf:RDF>

XML;
        $res->shouldBe($expected);
    }
}
