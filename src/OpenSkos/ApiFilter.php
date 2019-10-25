<?php

namespace App\OpenSkos;

use App\Ontology\Context;
use App\Ontology\OpenSkos;
use Symfony\Component\HttpFoundation\Request;

final class ApiFilter
{
    const aliases = [
        'acceptedBy' => 'openskos:acceptedBy',
        'creator' => 'dcterms:creator',
        'dateAccepted' => 'dcterms:dateAccepted',
        'dateSubmitted' => 'dcterms:dateSubmitted',
        'deleted' => 'openskos:deleted',
        'deletedBy' => 'openskos:deletedBy',
        'institutions' => 'openskos:tenant',
        'literalForm' => 'skosxl:literalForm',
        'modified' => 'dcterms:modified',
        'modifiedBy' => 'openskos:modifiedBy',
        'status' => 'openskos:status',
    ];

    const types = [
        'default' => 'csv',
        'dcterms:dateAccepted' => 'xsd:duration',
        'dcterms:dateSubmitted' => 'xsd:duration',
        'dcterms:modified' => 'xsd:duration',
        'openskos:deleted' => 'xsd:duration',
        'openskos:status' => OpenSkos::STATUSES,
    ];

    const entity = [
        'openskos:tenant' => 'institution',
    ];

    const international = [
        'skosxl:literalForm',
    ];

    const TYPE_STRING = 'string';
    const TYPE_URI = 'uri';

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var string|null
     */
    private $lang = null;

    public function __construct(
        Request $request
    ) {
        $params = $request->query->get('filter', []);

        // Extract filter language
        if (isset($params['lang'])) {
            $this->lang = $params['lang'];
            unset($params['lang']);
        }

        // Add all filters
        foreach ($params as $preficate => $value) {
            $this->addFilter($preficate, $value);
        }
    }

    /**
     * @param string $predicate
     * @param mixed  $value
     *
     * @return self
     */
    public function addFilter(
        string $predicate,
        $value
    ): self {
        // Handle aliases
        while (isset(static::aliases[$predicate])) {
            $predicate = static::aliases[$predicate];
        }

        // Transform URL into prefix
        foreach (Context::prefixes as $prefix => $namespace) {
            if (substr($predicate, 0, strlen($namespace)) === $namespace) {
                $predicate = $prefix.':'.(substr($predicate, strlen($namespace)));
                break;
            }
        }

        // Disallow unknown prefixes
        $tokens = explode(':', $predicate);
        $prefix = $tokens[0];
        if (!isset(Context::prefixes[$prefix])) {
            return $this;
        }

        // Detect field type
        $type = static::types[$predicate] ?? 'csv';

        // Disallow unknown enum
        if (is_array($type)) {
            if (!in_array($value, $type, true)) {
                return $this;
            }
        }

        // Handle csv
        if ('csv' === $type) {
            $value = str_getcsv($value);
        }

        // Register the filter
        $this->filters[$predicate] = $value;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public static function isUuid($value): bool
    {
        $retval = false;

        if (is_string($value) &&
            36 == strlen($value) &&
            preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $value)) {
            $retval = true;
        }

        return $retval;
    }

    private static function buildFilter($predicate, $value, $lang = null)
    {
        $output = [];

        if (is_array($value)) {
            foreach ($value as $entry) {
                $filter = static::buildFilter($predicate, $entry, $lang);
                if (!is_null($filter)) {
                    $output = array_merge($output, $filter);
                }
            }

            return $output;
        }

        // Remove language if it's not supported on this field
        if (!in_array($predicate, static::international, true)) {
            $lang = null;
        }

        // Build the right predicate
        $entity = static::entity[$predicate] ?? 'subject';
        $tokens = explode(':', $predicate);
        $prefix = array_shift($tokens);
        $field = implode(':', $tokens);
        $predicate = Context::prefixes[$prefix].$field;

        // Add url or string to the output
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $output[] = [
                'predicate' => $predicate,
                'value' => $value,
                'type' => static::TYPE_URI,
                'entity' => $entity,
            ];
        } else {
            $output[] = [
                'predicate' => $predicate,
                'value' => $value,
                'type' => static::TYPE_STRING,
                'entity' => $entity,
                'lang' => $lang,
            ];
        }

        return $output;
    }

    public function buildFilters(): array
    {
        $output = [];

        foreach ($this->filters as $preficate => $value) {
            $filters = $this->buildFilter($preficate, $value, $this->lang);
            if (!is_null($filters)) {
                $output = array_merge($output, $filters);
            }
        }

        return $output;
    }
}
