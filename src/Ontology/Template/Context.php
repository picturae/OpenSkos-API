<?php

namespace App\Ontology\Template;

?>
<?= "<?php\n"; ?>

namespace App\Ontology;

final class Context
{
    const prefixes = [
<?php foreach ($context as $namespace) { ?>
        '<?= $namespace['prefix']; ?>' => <?= $namespace['name']; ?>::NAME_SPACE,
<?php } /* foreach context as namespace */ ?>
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
