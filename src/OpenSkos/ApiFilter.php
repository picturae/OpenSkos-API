<?php

namespace App\OpenSkos;

use App\Annotation\Error;
use App\Annotation\ErrorInherit;
use App\Exception\ApiException;
use App\Ontology\Context;
use App\Ontology\OpenSkos;
use App\OpenSkos\Institution\Institution;
use App\OpenSkos\Set\Set;
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
        'prefLabel'     => 'skos:prefLabel',
        'sets'          => 'openskos:set',
        'status'        => 'openskos:status',
        'tenant'        => 'openskos:tenant',
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

    protected $normalize = [];

    /**
     * @ErrorInherit(class=ApiFilter::class, method="addFilter")
     *
     * TODO:
     *   add support for direct/aliased params on the url (add, not replace)
     *   example: ?institutions= instead of ?filter[institutions]=
     *   why: will be closer to the spec by BEG
     */
    public function __construct(
        Request $request,
        SetRepository $setRepository
    ) {
        $this->setRepository = $setRepository;

        // Initialize normalizers (can't load function in constants...)
        $this->normalize[Institution::class] = function (Institution $institution) {
            $code = $institution->getValue(OpenSkos::CODE);

            return $code ? $code->__toString() : '';
        };
        $this->normalize[Iri::class] = function (Iri $iri) {
            return $iri;
        };
        $this->normalize[InternalResourceId::class] = function (InternalResourceId $iri) {
            return $iri;
        };

        // Fetch filters
        $params = $request->query->get('filter', []);

        // Add text[n]=N&fields=predicate,predicate support
        $texts  = $request->query->get('text', []);
        $fields = str_getcsv($request->query->get('fields', ''));
        foreach ($fields as $field) {
            if (isset($params[$field]) && (!is_array($params[$field]))) {
                $params[$field] = [$params[$field]];
            }
            if (isset($params[$field])) {
                foreach ($texts as $text) {
                    array_push($params[$field], $text);
                }
            } else {
                $params[$field] = $texts;
            }
        }

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

    /**
     * @ErrorInherit(class=Context::class, method="decodeUri")
     * @ErrorInherit(class=Context::class, method="fullUri"  )
     */
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
     *
     * @throws ApiException
     *
     * @Error(code="apifilter-addfilter-could-not-stringify-object",
     *        status=400,
     *        description="Could not stringify given object",
     *        fields={"class"}
     * )
     *
     * @ErrorInherit(class=ApiFilter::class         , method="fromFullUri")
     * @ErrorInherit(class=Context::class           , method="literaltype")
     * @ErrorInherit(class=InternalResourceId::class, method="__construct")
     * @ErrorInherit(class=Iri::class               , method="__construct")
     * @ErrorInherit(class=Iri::class               , method="getUri"     )
     * @ErrorInherit(class=Set::class               , method="iri"        )
     * @ErrorInherit(class=SetRepository::class     , method="findOneBy"  )
     */
    public function addFilter(
        string $predicate,
        $value
    ): self {
        if (is_null($value)) {
            return $this;
        }

        if (is_object($value)) {
            $class = get_class($value);
            if (isset($this->normalize[$class])) {
                $value = $this->normalize[$class]($value);
            } elseif (method_exists($value, '__toString')) {
                $value = $value->__toString();
            } else {
                throw new ApiException('apifilter-addfilter-could-not-stringify-object', [
                    'class' => $class,
                ]);
            }
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
     *
     * @ErrorInherit(class=Context::class, method="fullUri")
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
     *
     * @ErrorInherit(class=ApiFilter::class, method="buildFilter")
     * @ErrorInherit(class=ApiFilter::class, method="fromFullUri")
     * @ErrorInherit(class=Context::class  , method="literaltype")
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
                        case 'xsd:string':
                            $filterField = 's_'.$shortfield;
                            break;
                        default:
                            continue 2;
                    }

                    // Register the filters
                    $filterValue                = trim($jenaFilter['value']);
                    $filterName                 = lcfirst(str_replace(' ', '', ucwords(str_replace(':', ' ', $predicate.':filter'))));
                    $solrFilters[$filterName][] = "${filterField}:\"".(strpos($filterValue, ' ') ? "\"${filterValue}\"" : $filterValue)."\"";
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
     *
     * @ErrorInherit(class=ApiFilter::class, method="buildFilters")
     */
    public function __toArray($type = 'jena'): array
    {
        return $this->buildFilters($type);
    }
}
