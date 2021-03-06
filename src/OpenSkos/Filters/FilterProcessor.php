<?php

declare(strict_types=1);

namespace App\OpenSkos\Filters;

use App\Annotation\Error;
use App\Exception\ApiException;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;
use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;

final class FilterProcessor
{
    //Filter Types
    const TYPE_URI    = 'uri';
    const TYPE_UUID   = 'uuid';
    const TYPE_STRING = 'string';

    //Group for filter
    const ENTITY_INSTITUTION   = 'institution';
    const ENTITY_SET           = 'set';
    const ENTITY_CONCEPTSCHEME = 'conceptscheme';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var FilterProcessorHelper
     */
    private $filter_helper;

    /**
     * FilterProcessor constructor.
     * @param Connection $connection
     * @param ContainerInterface $container
     * @param FilterProcessorHelper $filter_helper
     */
    public function __construct(Connection $connection, ContainerInterface $container, FilterProcessorHelper $filter_helper)
    {
        $this->connection = $connection;
        $this->container  = $container;
        $this->filter_helper  = $filter_helper;
    }

    /**
     * @param $value
     *
     * @return bool
     */
    public static function isUuid($value)
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
     * @return bool
     */
    public function hasPublisher(array $filters)
    {
        $has_publisher = false;
        foreach ($filters as $elem) {
            if (FilterProcessor::TYPE_URI == $elem['type']) {
                $has_publisher = true;
                break;
            }
        }

        return $has_publisher;
    }


    /**
     * @param bool $resolve_publisher if true, resolve a publisher uri to a tenant code. (This involves an extra Jena query)
     *
     * @return array
     *
     * @throws ApiException
     * @Error(code="filterprocessor-build-institution-filters-uuid-not-supported",
     *        status=400,
     *        description="The search by UUID for institutions could not be retrieved (Predicate is not used in Jena Store)."
     * )
     */
    public function buildInstitutionFilters(array $filterList, bool $resolve_publisher = false)
    {
        $dataOut = [];

        foreach ($filterList as $filter) {
            if (self::isUuid($filter)) {
                throw new ApiException('filterprocessor-build-institution-filters-uuid-not-supported');
            } elseif (filter_var($filter, FILTER_VALIDATE_URL)) {
                if (true === $resolve_publisher) {
                    $code      = $this->filter_helper->resolveInstitutionsToCode(new Iri($filter));
                    $dataOut[] = ['predicate' => OpenSkos::TENANT, 'value' => $code, 'type' => self::TYPE_STRING, 'entity' => self::ENTITY_INSTITUTION];
                } else {
                    $dataOut[] = ['predicate' => DcTerms::PUBLISHER, 'value' => $filter, 'type' => self::TYPE_URI, 'entity' => self::ENTITY_INSTITUTION];
                }
            } else {
                $dataOut[] = ['predicate' => OpenSkos::TENANT, 'value' => $filter, 'type' => self::TYPE_STRING, 'entity' => self::ENTITY_INSTITUTION];
            }
        }

        return $dataOut;
    }

    /**
     * @return array
     *
     * @throws ApiException
     *
     * @Error(code="filterprocessor-build-set-filters-uuid-not-supported",
     *        status=400,
     *        description="The search by UUID for sets could not be retrieved (Predicate is not used in Jena Store)."
     * )
     */
    public function buildSetFilters(array $filterList, bool $resolve_code = false)
    {
        $dataOut = [];

        foreach ($filterList as $filter) {
            if (self::isUuid($filter)) {
                throw new ApiException('filterprocessor-build-set-filters-uuid-not-supported');
            } elseif (filter_var($filter, FILTER_VALIDATE_URL)) {
                $dataOut[] = ['predicate' => OpenSkos::SET, 'value' => $filter, 'type' => self::TYPE_URI, 'entity' => self::ENTITY_SET];
            } else {
                if (true === $resolve_code) {
                    $code      = $this->filter_helper->resolveSetToUriLiteral($filter);
                    $dataOut[] = ['predicate' => OpenSkos::SET, 'value' => $code, 'type' => self::TYPE_URI, 'entity' => self::ENTITY_SET];
                } else {
                    $dataOut[] = ['predicate' => OpenSkos::SET, 'value' => $filter, 'type' => self::TYPE_STRING, 'entity' => self::ENTITY_SET];
                }
            }
        }

        return $dataOut;
    }
}
