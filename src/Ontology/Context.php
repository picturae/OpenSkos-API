<?php

namespace App\Ontology;

final class Context
{
    const prefixes = [
        'dcmi' => Dcmi::NAME_SPACE,
        'dc' => Dc::NAME_SPACE,
        'dcterms' => DcTerms::NAME_SPACE,
        'foaf' => Foaf::NAME_SPACE,
        'openskos' => OpenSkos::NAME_SPACE,
        'org' => Org::NAME_SPACE,
        'owl' => Owl::NAME_SPACE,
        'rdf' => Rdf::NAME_SPACE,
        'rdfs' => Rdfs::NAME_SPACE,
        'skos' => Skos::NAME_SPACE,
        'skosxl' => SkosXl::NAME_SPACE,
        'vcard' => VCard::NAME_SPACE,
        'xsd' => Xsd::NAME_SPACE,
    ];

    /**
     * Build a context based on short names.
     *
     * @param array $names
     *
     * @return array
     */
    public static function build($names = []): array
    {
        $result = [];

        foreach ($names as $name) {
            if (isset(self::prefixes[$name])) {
                $result[$name] = self::prefixes[$name];
            }
        }

        return $result;
    }

    /**
     * array_walk_recursive, including branch nodes.
     *
     * @param array    $arr
     * @param callable $callback Arguments: <item, key>
     */
    private static function walk(array $arr, callable $callback): void
    {
        /* var_dump($arr); */
        foreach ($arr as $key => $value) {
            call_user_func($callback, $value, $key);
            if (is_array($value)) {
                self::walk($value, $callback);
            }
        }
    }

    /**
     * Return the namespace belonging to the uri.
     *
     * @param mixed $uri
     *
     * @return string|bool
     */
    private static function detectNamespaceFromUri($uri)
    {
        if (!is_string($uri)) {
            return false;
        }
        foreach (self::prefixes as $prefix => $namespace) {
            if (substr($uri, 0, strlen($namespace)) === $namespace) {
                return $prefix;
            }
        }

        return false;
    }

    /**
     * Automatically detect namespaces from a given graph.
     *
     * @param \EasyRdf_Graph $graph
     *
     * @return array
     */
    public static function detect(\EasyRdf_Graph $graph): array
    {
        $result = [];
        $known = self::prefixes;

        // Walk through the whole graph
        // CAUTION: this may result in a performance hit
        self::walk(
            $graph->toRdfPhp(),
            function ($item, string $key) use (&$result, $known) {
                $keyNamespace = self::detectNamespaceFromUri($key);
                $itemNamespace = self::detectNamespaceFromUri($item);

                if (false !== $keyNamespace) {
                    $result[$keyNamespace] = $known[$keyNamespace];
                }
                if (false !== $itemNamespace) {
                    $result[$itemNamespace] = $known[$itemNamespace];
                }
            }
        );

        // Sort the result to ensure the same results
        ksort($result);

        return $result;
    }
}
