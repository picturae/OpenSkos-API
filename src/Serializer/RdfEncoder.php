<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Annotation\Error;
use App\EasyRdf\Serializer\OpenSkosJsonLdSerializer;
use App\Exception\ApiException;
use App\Ontology\Context;
use App\Ontology\OpenSkos;
use App\OpenSkos\Label\Label;
use App\Rdf\Format\JsonLd;
use App\Rdf\Format\Ntriples;
use App\Rdf\Format\RdfFormatFactory;
use App\Rdf\Format\RdfXml;
use App\Rdf\Format\Turtle;
use App\Rdf\Iri;
use App\Rdf\Literal\BooleanLiteral;
use App\Rdf\Literal\DatetimeLiteral;
use App\Rdf\Literal\Literal;
use App\Rdf\Literal\StringLiteral;
use EasyRdf_Graph;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\NormalizationAwareInterface;

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
            JsonLd::instance()->name()   => 'jsonld',
            RdfXml::instance()->name()   => 'rdfxml',
            Ntriples::instance()->name() => 'ntriples',
            Turtle::instance()->name()   => 'turtle',
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
     * @param mixed  $data
     * @param string $format
     *
     * @return string
     *
     * @Error(code="serializer-rdfencoder-encode-data-not-iterable",
     *        status=500,
     *        description="Given data is not an iterable",
     *        fields={"received"}
     * )
     */
    public function encode($data, $format, array $context = [])
    {
        if ((!is_iterable($data)) && (!($data instanceof EasyRdf_Graph))) {
            $type = gettype($data);
            if ('object' == $type) {
                $type .= '('.get_class($data).')';
            }
            throw new ApiException('serializer-rdfencoder-encode-data-not-iterable', [
                'received' => $type,
            ]);
        }

        if ($data instanceof EasyRdf_Graph) {
            $graph = $data;
        } else {
            $graph = $this->tripleSetToEasyRdfGraph($data);
        }

        $options = [];

        if ('json-ld' === $format) {
            $context = json_encode([
                '@context' => Context::detect($graph),
            ]);

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
                'pretty'  => $pretty,
                'syntax'  => $syntax,
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
     * @param $triples
     *
     * @psalm-suppress InvalidMethodCall
     */
    private function tripleSetToEasyRdfGraph($triples): EasyRdf_Graph
    {
        $graph = new EasyRdf_Graph('http://openskos.org');
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);

        if ((is_array($triples) && count($triples) > 0) || (is_iterable($triples) && $triples->valid())) {
            $this->serializeLevelOfTriples($graph, $triples);
        }

        return $graph;
    }

    private function serializeLevelOfTriples(&$graph, iterable $triples, $recursionLevel = 0)
    {
        foreach ($triples as $triple) {
            if ($triple instanceof Label) {
                $tripleSubject = $triple->getSubject();
                $labelSubject  = $triple->getChildSubject();

                $this->serializeLevelOfTriples($graph, $triple->triples(), $recursionLevel + 1);

                //Add this node to the parent
                $predicate = $triple->getType();

                if (isset($tripleSubject) && isset($predicate)) {
                    $graph->addResource(
                        $tripleSubject->getUri(),
                        $predicate->getUri(),
                        $labelSubject->getUri()
                    );
                }
                continue;
            }
            $subject   = $triple->getSubject();
            $predicate = $triple->getPredicate();
            $object    = $triple->getObject();

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
