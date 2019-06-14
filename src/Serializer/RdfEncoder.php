<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Rdf\RdfHeaders;
use EasyRdf_Graph;
use App\Ontology\OpenSkos;
use App\Rdf\Literal;
use App\Rdf\Iri;
use EasyRdf_Literal_Boolean;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;




class RdfEncoder implements EncoderInterface, DecoderInterface
{
    /**
     * @param $formatIn
     *
     * @return string
     */
    private function format2EasyRdfFormat($formatIn)
    {
        $formatOut = $formatIn;

        switch ($formatIn) {
           case  RdfHeaders::FORMAT_JSON_LD:
               $formatOut = 'jsonld';
               break;
           default:
               //Do absolutely nothing
               break;
       }

        return $formatOut;
    }

    /**
     * EasyRdf is used an an intermediate format between the TripleStore and its serialised formats.
     */
    public function encode($data, $format, array $context = [])
    {
        $easyRdfFormat = $this->format2EasyRdfFormat($format);

        $graph = $this->arrayToEasyRdfGraph($data);

        return $graph->serialise($easyRdfFormat);
    }

    /**
     * @param string $format
     *
     * @return bool
     */
    public function supportsEncoding($format)
    {
        return in_array($format, [RdfHeaders::FORMAT_JSON_LD, RdfHeaders::FORMAT_RDF_XML]);
    }

    /**
     * @param string $data
     * @param string $format
     * @param array  $context
     *
     * @return EasyRdf_Graph|mixed
     *
     * @throws \EasyRdf_Exception
     */
    public function decode($data, $format, array $context = [])
    {
        $easyRdfFormat = $this->format2EasyRdfFormat($format);
        $graph = new EasyRdf_Graph($_REQUEST['uri']);
        $graph->parse($_REQUEST['data'], $easyRdfFormat, 'http://openskos.org');

        return $graph;
    }

    /**
     * @param string $format
     *
     * @return bool
     */
    public function supportsDecoding($format)
    {
        return in_array($format, [RdfHeaders::FORMAT_JSON_LD, RdfHeaders::FORMAT_RDF_XML]);
    }

    private function arrayToEasyRdfGraph($data)
    {
        $graph = new EasyRdf_Graph('http://openskos.org');
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);

        $OSEntityCollection = $data;

        foreach ($OSEntityCollection as $osEntity) {
            $subject = $osEntity->getSubject()->getUri();

            $entity = $graph->resource($subject, 'rdf:Description');

            $mapping = $osEntity->getMapping();
            $properties = $osEntity->getProperties();
            foreach ($mapping as $key => $property) {
                if (isset($properties[$key])) {
                    if ($properties[$key] instanceof Literal) {
                        if (null === $properties[$key]->getType()) {
                            $entity->addLiteral(
                                $property,
                                $properties[$key]->getValue(),
                                $properties[$key]->getLanguage(),
                            );
                        } elseif (\App\Rdf\Literal::TYPE_BOOL === $properties[$key]->getType()) {
                            $res = new EasyRdf_Literal_Boolean($properties[$key]->getValue());
                            $entity->add($property, $res);
                        } else {
                            $entity->addLiteral(
                                $property,
                                $properties[$key]->getValue(),
                                $properties[$key]->getLanguage(),
                            );
                        }
                    } elseif ($properties[$key] instanceof Iri) {
                        $entity->addResource($property, $properties[$key]->getUri());
                    }
                }
            }
        }

        return $graph;
    }
}
