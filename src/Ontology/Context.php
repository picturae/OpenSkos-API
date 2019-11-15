<?php

namespace App\Ontology;

final class Context
{
    const prefixes = [
        'dc'       => Dc::NAME_SPACE,
        'dcmi'     => Dcmi::NAME_SPACE,
        'dcterms'  => DcTerms::NAME_SPACE,
        'foaf'     => Foaf::NAME_SPACE,
        'http'     => Http::NAME_SPACE,
        'openskos' => OpenSkos::NAME_SPACE,
        'org'      => Org::NAME_SPACE,
        'owl'      => Owl::NAME_SPACE,
        'rdf'      => Rdf::NAME_SPACE,
        'rdfs'     => Rdfs::NAME_SPACE,
        'skos'     => Skos::NAME_SPACE,
        'skosxl'   => SkosXl::NAME_SPACE,
        'vcard'    => VCard::NAME_SPACE,
        'xsd'      => Xsd::NAME_SPACE,
    ];

    const dataclass = [
        'foaf:Person'            => '\App\OpenSkos\User\User',
        'openskos:set'           => '\App\OpenSkos\Set\Set',
        'org:FormalOrganization' => '\App\OpenSkos\Institution\Institution',
        'skos:Concept'           => '\App\OpenSkos\Concept\Concept',
        'skos:ConceptScheme'     => '\App\OpenSkos\ConceptScheme\ConceptScheme',
        'skosxl:Label'           => '\App\OpenSkos\Label\Label',
    ];

    const namespaces = [
        'dc'       => Dc::class,
        'dcmi'     => Dcmi::class,
        'dcterms'  => DcTerms::class,
        'foaf'     => Foaf::class,
        'http'     => Http::class,
        'openskos' => OpenSkos::class,
        'org'      => Org::class,
        'owl'      => Owl::class,
        'rdf'      => Rdf::class,
        'rdfs'     => Rdfs::class,
        'skos'     => Skos::class,
        'skosxl'   => SkosXl::class,
        'vcard'    => VCard::class,
        'xsd'      => Xsd::class,
    ];

    /**
     * Build a context based on short names.
     *
     * @param array $names
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
     * @param callable $callback Arguments: <item, key>
     */
    private static function walk(array $arr, callable $callback): void
    {
        foreach ($arr as $key => $value) {
            call_user_func($callback, $value, $key);
            if (is_array($value)) {
                self::walk($value, $callback);
            }
        }
    }

    /**
     * Detect prefix AND field from uri.
     */
    public static function decodeUri(string $uri): ?array
    {
        $tokens = explode(':', $uri);
        if (2 === (count($tokens)) && (isset(static::prefixes[$tokens[0]]))) {
            return $tokens;
        }

        $prefix = self::detectNamespaceFromUri($uri);
        if (false === $prefix) {
            return null;
        }
        $field = substr($uri, strlen(static::prefixes[$prefix]));

        return [$prefix, $field];
    }

    /**
     * Turn a uri or short notation into full uri.
     */
    public static function fullUri(string $uri): ?string
    {
        $decoded = static::decodeUri($uri);
        if (is_null($decoded)) {
            return null;
        }

        return static::prefixes[$decoded[0]].$decoded[1];
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
     */
    public static function detect(\EasyRdf_Graph $graph): array
    {
        $result = [];
        $known  = self::prefixes;

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

    /**
     * Registers all known namespaces into EasyRdf.
     */
    public static function setupEasyRdf(): void
    {
        foreach (self::prefixes as $prefix => $namespace) {
            \EasyRdf_Namespace::set($prefix, $namespace);
        }
    }
}
