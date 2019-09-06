<?php

declare(strict_types=1);

namespace App\Serializer;

use App\EasyRdf\Serializer\OpenSkosJsonLdSerializer;
use App\OpenSkos\Label\Label;
use App\Rdf\Format\JsonLd;
use App\Rdf\Format\Ntriples;
use App\Rdf\Format\RdfFormatFactory;
use App\Rdf\Format\RdfXml;
use App\Rdf\Format\Turtle;
use App\Rdf\Literal\BooleanLiteral;
use App\Rdf\Literal\DatetimeLiteral;
use App\Rdf\Literal\StringLiteral;
use App\Rdf\Triple;
use EasyRdf_Graph;
use App\Ontology\OpenSkos;
use App\Rdf\Literal\Literal;
use App\Rdf\Iri;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\NormalizationAwareInterface;
use Symfony\Component\Serializer\Exception\UnsupportedException;

class RdfEncoder implements EncoderInterface, NormalizationAwareInterface
{
    /**
     * @var RdfFormatFactory
     */
    private $formatFactory;

    private $apiRequest;

    /**
     * @var array<string,string>
     */
    private $formatMap = [];

    public function __construct(
        RdfFormatFactory $formatFactory
    ) {
        $this->formatFactory = $formatFactory;

        $this->formatMap = [
            JsonLd::instance()->name() => 'jsonld',
            RdfXml::instance()->name() => 'rdfxml',
            Ntriples::instance()->name() => 'ntriples',
            Turtle::instance()->name() => 'turtle',
        ];
    }

    /**
     * @param string $format
     *
     * @return bool
     */
    public function supportsEncoding($format)
    {
        return $this->formatFactory->exists($format);
    }

    /**
     * EasyRdf is used an an intermediate format between the TripleStore and its serialised formats.
     *
     * @param $data
     * @param string $format
     * @param array  $context
     *
     * @return string
     */
    public function encode($data, $format, array $context = [])
    {
        if (!is_iterable($data)) {
            throw new UnsupportedException('data is not an iterable');
        }

        /** @var iterable<int,Triple> $data */
        $graph = $this->tripleSetToEasyRdfGraph($data);

        $options = [];

        if ('json-ld' === $format) {
            $context = <<<CONTEXT
{
  "@context": {
    "openskos": "http://openskos.org/xmlns#",
    "skos": "http://www.w3.org/2004/02/skos/core#",
    "dcterms": "http://purl.org/dc/terms/",
    "dc": "http://purl.org/dc/"
  }
}
CONTEXT;

            $serialiser = new OpenSkosJsonLdSerializer();

            /*
             * A couple of undocumented options. pretty formatting, and flattened or expanded outputs
             */
            $pretty = isset($_REQUEST['pretty']) ? filter_var($_REQUEST['pretty'], FILTER_VALIDATE_BOOLEAN) : false;

            $syntax = (isset($_REQUEST['syntax']) &&
                        in_array($_REQUEST['syntax'], ['compact', 'expand', 'flatten'], true)
            ) ? $_REQUEST['syntax'] : 'compact';

            $processed_data = $serialiser->serialise($graph, 'jsonld', [
                'compact' => true,
                'context' => $context,
                'pretty' => $pretty,
                'syntax' => $syntax,
            ]);

            $serialised_data = $processed_data;
        } else {
            $serialised_data = $graph->serialise($this->formatMap[$format] ?? $format, $options);
        }

        return $serialised_data;
    }

    private function literalToEasyRdf(Literal $literal): \EasyRdf_Literal
    {
        if ($literal instanceof BooleanLiteral) {
            $value = new \EasyRdf_Literal_Boolean($literal->value());
        } elseif ($literal instanceof DatetimeLiteral) {
            $value = new \EasyRdf_Literal_DateTime($literal->value());
        } elseif ($literal instanceof StringLiteral) {
            $value = new \EasyRdf_Literal(
                $literal->value(),
                $literal->lang()
            );
        } else {
            $value = new \EasyRdf_Literal(
                (string) $literal,
                null,
                $literal->typeIri()->getUri()
            );
        }

        return $value;
    }

    /**
     * @param iterable<int,Triple> $triples
     *
     * @return EasyRdf_Graph
     */
    private function tripleSetToEasyRdfGraph(iterable $triples): EasyRdf_Graph
    {
        $graph = new EasyRdf_Graph('http://openskos.org');
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);

        if ($triples->valid()) {
            $this->serializeLevelOfTriples($graph, $triples);
        }

        return $graph;
    }

    /**
     * @param $triples
     *
     * @return Iri|null
     */
    public function getResourceSubject($triples): ?Iri
    {
        /*
        A design "quirk" of the resource objects, is that they store a list of triples all with the same subject, but don't ever
        set a subject specifically
        */
        $subject = null;
        foreach ($triples as $triple) {         //We could be receiving a \Generator object
            if ($triple instanceof Triple) {
                $subject = $triple->getSubject();
                break;
            }
        }

        return $subject;
    }

    private function serializeLevelOfTriples(&$graph, iterable $triples, $recursionLevel = 0)
    {
        $resourceSubject = $this->getResourceSubject($triples);

        foreach ($triples as $triple) {
            if ($triple instanceof Label) {
                if ($resourceSubject) {
                    $this->serializeLevelOfTriples($graph, $triple->triples(), $recursionLevel + 1);

                    //Add this node to the parent
                    $labelSubject = $this->getResourceSubject($triple->triples());
                    $predicate = $triple->getType();

                    if (isset($labelSubject) && isset($predicate)) {
                        $graph->addResource(
                            $resourceSubject->getUri(),
                            $predicate->getUri(),
                            $labelSubject->getUri()
                        );
                    }
                }
                continue;
            }
            $subject = $triple->getSubject();
            $predicate = $triple->getPredicate();
            $object = $triple->getObject();

            if ($object instanceof Literal) {
                $graph->addLiteral(
                    $subject->getUri(),
                    $predicate->getUri(),
                    $this->literalToEasyRdf($object)
                );
            } elseif ($object instanceof Iri) {
                $graph->addResource(
                    $subject->getUri(),
                    $predicate->getUri(),
                    $object->getUri()
                );
            }
        }
    }
}
