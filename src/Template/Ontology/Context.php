<?php

namespace App\Template\Ontology;

?>
<?= "<?php\n"; ?>

/* * * * * * * * * * * * * * *\
 * CAUTION: GENERATED CLASS  *
\* * * * * * * * * * * * * * */

namespace App\Ontology;

use App\Annotation\ErrorInherit;

final class Context
{
    const prefixes = [
<?php foreach ($context as $namespace) { ?>
        '<?= $namespace['prefix']; ?>' => <?= $namespace['name']; ?>::NAME_SPACE,
<?php } /* foreach context as namespace */ ?>
    ];

    const dataclass = [
<?php foreach ($dataclass as $key => $value) { ?>
        '<?= $key; ?>' => '\<?= $value; ?>',
<?php } /* foreach datatype as key value */ ?>
    ];

    const namespaces = [
<?php foreach ($context as $namespace) { ?>
        '<?= $namespace['prefix']; ?>' => <?= $namespace['name']; ?>::class,
<?php } /* foreach context as namespace */ ?>
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
     *
     * @ErrorInherit(class=Context::class, method="detectNamespaceFromUri")
     */
    public static function decodeUri(?string $uri): ?array
    {
        if (is_null($uri)) {
            return null;
        }

        $tokens = explode(':', $uri);
        if (2 === (count($tokens)) && (isset(static::prefixes[$tokens[0]])) && ($tokens[0] !== 'http')) {
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
     * Returns the literal type for a field (or null)
     *
     * @ErrorInherit(class=Context::class, method="decodeUri")
     */
    public static function literaltype(?string $uri): ?string
    {
        $decoded = static::decodeUri($uri);
        if (is_null($decoded)) {
            return null;
        }

        // Namespace not known = done
        if (!isset(static::namespaces[$decoded[0]])) {
            return null;
        }

        return static::namespaces[$decoded[0]]::literaltypes[static::fullUri($uri) ?? ''] ?? null;
    }

    /**
     * Turn a uri or short notation into full uri.
     *
     * @ErrorInherit(class=Context::class, method="decodeUri")
     */
    public static function fullUri(?string $uri): ?string
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
     *
     * @ErrorInherit(class=Context::class, method="detectNamespaceFromUri")
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
