<?php
/**
 * EasyRdf.
 */

namespace App\EasyRdf\Serializer;

use EasyRdf_Exception;
use EasyRdf_Graph;
use LogicException;

/**
 * Class to serialise an EasyRdf_Graph to JSON-LD.
 *
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class OpenSkosJsonLdSerializer extends \EasyRdf_Serialiser
{
    public function __construct()
    {
        if (!class_exists('\ML\JsonLD\JsonLD')) {
            throw new LogicException('Please install "ml/json-ld" dependency to use JSON-LD serialisation');
        }

        parent::__construct();
    }

    /**
     * @param EasyRdf_Graph $graph
     * @param string        $format
     *
     * @throws EasyRdf_Exception
     *
     * @return string
     */
    public function serialise($graph, $format, array $options = [])
    {
        parent::checkSerialiseParams($graph, $format);

        if ('jsonld' != $format) {
            throw new EasyRdf_Exception(__CLASS__.' does not support: '.$format);
        }

        $ld_graph = new \ML\JsonLD\Graph();
        $nodes = []; // cache for id-to-node association

        foreach ($graph->toRdfPhp() as $resource => $properties) {
            if (array_key_exists($resource, $nodes)) {
                $node = $nodes[$resource];
            } else {
                $node = $ld_graph->createNode($resource);
                $nodes[$resource] = $node;
            }

            foreach ($properties as $property => $values) {
                foreach ($values as $value) {
                    if ('bnode' == $value['type'] or 'uri' == $value['type']) {
                        if (array_key_exists($value['value'], $nodes)) {
                            $_value = $nodes[$value['value']];
                        } else {
                            $_value = $ld_graph->createNode($value['value']);
                            $nodes[$value['value']] = $_value;
                        }
                    } elseif ('literal' == $value['type']) {
                        if (isset($value['lang'])) {
                            $_value = new \ML\JsonLD\LanguageTaggedString($value['value'], $value['lang']);
                        } elseif (isset($value['datatype'])) {
                            $_value = new \ML\JsonLD\TypedValue($value['value'], $value['datatype']);
                        } else {
                            $_value = $value['value'];
                        }
                    } else {
                        throw new EasyRdf_Exception('Unable to serialise object to JSON-LD: '.$value['type']);
                    }

                    if ('http://www.w3.org/1999/02/22-rdf-syntax-ns#type' == $property) {
                        $node->addType($_value);
                    } else {
                        $node->addPropertyValue($property, $_value);
                    }
                }
            }
        }

        // expanded form
        $data = $ld_graph->toJsonLd();

        $compact_context = $options['context'] ?? null;
        $pretty = $options['pretty'] ?? false;
        $syntax = $options['syntax'] ?? 'compact';

        if ('compact' === $syntax) {
            $dataOut = \ML\JsonLD\JsonLD::$syntax($data, $compact_context);
        } else {
            $dataOut = \ML\JsonLD\JsonLD::$syntax($data);
        }

        return \ML\JsonLD\JsonLD::toString($dataOut, $pretty);
    }
}
