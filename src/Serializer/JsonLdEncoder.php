<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Rdf\RdfHeaders;
use EasyRdf_Graph;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class JsonLdEncoder implements EncoderInterface, DecoderInterface
{
    /**
     * EasyRdf is used an an intermediate format between the TripleStore and its serialised formats.
     */
    public function encode($data, $format, array $context = [])
    {
        return $data->serialise('jsonld');
    }

    /**
     * @param string $format
     *
     * @return bool
     */
    public function supportsEncoding($format)
    {
        return RdfHeaders::FORMAT_JSON_LD === $format;
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
        $graph = new EasyRdf_Graph($_REQUEST['uri']);
        $graph->parse($_REQUEST['data'], 'jsonld', 'http://openskos.org');

        return $graph;
    }

    /**
     * @param string $format
     *
     * @return bool
     */
    public function supportsDecoding($format)
    {
        return RdfHeaders::FORMAT_JSON_LD === $format;
    }
}
