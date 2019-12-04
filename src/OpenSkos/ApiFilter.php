<?php

namespace App\OpenSkos;

use App\Ontology\Context;
use App\Ontology\OpenSkos;
use App\OpenSkos\Set\SetRepository;
use App\Rdf\Iri;
use Symfony\Component\HttpFoundation\Request;

final class ApiFilter
{
    const aliases = [
        'acceptedBy'    => 'openskos:acceptedBy',
        'creator'       => 'dcterms:creator',
        'dateAccepted'  => 'dcterms:dateAccepted',
        'dateSubmitted' => 'dcterms:dateSubmitted',
        'deleted'       => 'openskos:deleted',
        'deletedBy'     => 'openskos:deletedBy',
        'institutions'  => 'openskos:tenant',
        'literalForm'   => 'skosxl:literalForm',
        'modified'      => 'dcterms:modified',
        'modifiedBy'    => 'openskos:modifiedBy',
        'sets'          => 'openskos:set',
        'status'        => 'openskos:status',
        'tenant'        => 'openskos:tenant',
    ];

    const enums = [
        'openskos:status' => OpenSkos::STATUSES,
    ];

    const entity = [
        'openskos:tenant' => 'institution',
    ];

    const international = [
        'skosxl:literalForm',
    ];

    const TYPE_STRING = 'string';
    const TYPE_URI    = 'uri';

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var string|null
     */
    private $lang = null;

    /**
     * @var SetRepository
     */
    private $setRepository;

    public function __construct(
        Request $request,
        SetRepository $setRepository
    ) {
        $this->setRepository = $setRepository;

        $params = $request->query->get('filter', []);

        // Extract filter language
        if (isset($params['lang'])) {
            $this->lang = $params['lang'];
            unset($params['lang']);
        }

        // Add all filters
        foreach ($params as $predicate => $value) {
            $this->addFilter($predicate, $value);
        }
    }

    public static function fromFullUri(string $predicate): ?string
    {
        $parts = Context::decodeUri(Context::fullUri($predicate));
        if (is_null($parts)) {
            return null;
        }

        return implode(':', $parts);
    }

    /**
     * @param mixed $value
     */
    public function addFilter(
        string $predicate,
        $value
    ): self {
        if (is_null($value)) {
            return $this;
        }

        // Handle arrays
        if (is_array($value)) {
            foreach ($value as $entry) {
                $this->addFilter($predicate, $entry);
            }

            return $this;
        }

        // Handle aliases
        while (isset(static::aliases[$predicate])) {
            $predicate = static::aliases[$predicate];
        }

        // Transform URL into prefix
        $predicate = static::fromFullUri($predicate);
        if (is_null($predicate)) {
            return $this;
        }

        // Disallow unknown enum
        if (isset(static::enums[$predicate])) {
            if (!in_array($value, static::enums[$predicate], true)) {
                return $this;
            }
        }

        // Detect field type
        $type = Context::literaltype($predicate) ?? 'csv';

        switch ($type) {
            case 'openskos:set':
                // TODO: fetch sets & use the found iri
                $set = $this->setRepository->findOneBy(
                    new Iri(OpenSkos::CODE),
                    new InternalResourceId($value)
                );
                if (!is_null($set)) {
                    $value = $set->iri()->getUri();
                }
                break;
        }

        // Register the filter
        $this->filters[$predicate][] = $value;

        return $this;
    }

    /**
     * @param mixed $value
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

    /**
     * @param string       $predicate
     * @param array|string $value
     * @param string|null  $lang
     */
    private static function buildFilter($predicate, $value, $lang = null): array
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
        $entity    = static::entity[$predicate] ?? 'subject';
        $predicate = Context::fullUri($predicate);

        // Add url or string to the output
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $output[] = [
                'predicate' => $predicate,
                'value'     => $value,
                'type'      => static::TYPE_URI,
                'entity'    => $entity,
            ];
        } else {
            $output[] = [
                'predicate' => $predicate,
                'value'     => $value,
                'type'      => static::TYPE_STRING,
                'entity'    => $entity,
                'lang'      => $lang,
            ];
        }

        return $output;
    }

    /**
     * @param string $type
     */
    public function buildFilters($type = 'jena'): array
    {
        $output = [];

        // Build jena filters
        foreach ($this->filters as $predicate => $value) {
            $filters = $this->buildFilter($predicate, $value, $this->lang);
            $output  = array_merge($output, $filters);
        }

        switch ($type) {
            // Solr requires some translation but contains less data
            // That's why we build in jena-mode
            case 'solr':
                $solrFilters = [];
                $ucfirstfn   = create_function('$c', 'return ucfirst($c);');

                foreach ($output as $jenaFilter) {
                    // Fetch short predicate and data type
                    $predicate = static::fromFullUri($jenaFilter['predicate']);
                    $datatype  = Context::literaltype($predicate) ?? 'csv';
                    if (is_null($predicate)) {
                        continue;
                    }

                    // Build solr field
                    $filterField = null;
                    $tokens      = explode(':', $predicate);
                    $prefix      = array_shift($tokens);
                    $shortfield  = implode(':', $tokens);
                    switch ($datatype) {
                        case 'xsd:duration':
                        case 'xsd:dateTime':
                            $filterField = 'd_'.$shortfield;
                            break;
                        case 'csv':
                            $filterField = 's_'.$shortfield;
                            break;
                        default:
                            continue 2;
                    }

                    // Register the filters
                    $filterValue                = $jenaFilter['value'];
                    $filterName                 = lcfirst(str_replace(' ', '', ucwords(str_replace(':', ' ', $predicate.':filter'))));
                    $solrFilters[$filterName][] = "${filterField}:\"${filterValue}\"";
                }

                // OR all lists together
                foreach ($solrFilters as $name => $filterList) {
                    if (count($filterList) > 1) {
                        $solrFilters[$name] = '('.implode(' OR ', $filterList).')';
                    } else {
                        $solrFilters[$name] = array_pop($filterList);
                    }
                }

                return $solrFilters;
            case 'jena': return $output;
            default: return [];
        }
    }

    /**
     * @param string $type
     */
    public function __toArray($type = 'jena'): array
    {
        return $this->buildFilters($type);
    }
}
