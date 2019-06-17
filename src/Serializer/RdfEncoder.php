<?php

declare(strict_types=1);

namespace App\Serializer;

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

        return (string) $graph->serialise($this->formatMap[$format] ?? $format);
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

        foreach ($triples as $triple) {
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

        return $graph;
    }
}
