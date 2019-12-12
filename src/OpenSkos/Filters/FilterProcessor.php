<?php

declare(strict_types=1);

namespace App\OpenSkos\Filters;

use App\Annotation\Error;
use App\Exception\ApiException;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use Doctrine\DBAL\Connection;

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

    private $connection;

    /**
     * FilterProcessor constructor.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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
     * @return array
     *
     * @Error(code="filterprocessor-build-institution-filters-uuid-not-supported",
     *        status=400,
     *        description="The search by UUID for institutions could not be retrieved (Predicate is not used in Jena Store)."
     * )
     */
    public function buildInstitutionFilters(array $filterList)
    {
        $dataOut = [];

        foreach ($filterList as $filter) {
            if (self::isUuid($filter)) {
                throw new ApiException('filterprocessor-build-institution-filters-uuid-not-supported');
            } elseif (filter_var($filter, FILTER_VALIDATE_URL)) {
                $dataOut[] = ['predicate' => DcTerms::PUBLISHER, 'value' => $filter, 'type' => self::TYPE_URI, 'entity' => self::ENTITY_INSTITUTION];
            } else {
                $dataOut[] = ['predicate' => OpenSkos::TENANT, 'value' => $filter, 'type' => self::TYPE_STRING, 'entity' => self::ENTITY_INSTITUTION];
            }
        }

        return $dataOut;
    }

    /**
     * @return array
     *
     * @Error(code="filterprocessor-build-set-filters-uuid-not-supported",
     *        status=400,
     *        description="The search by UUID for sets could not be retrieved (Predicate is not used in Jena Store)."
     * )
     * @Error(code="filterprocessor-build-set-filters-search-by-string",
     *        status=400,
     *        description="The search by string for sets could not be retrieved (Predicate is not used in Jena Store)."
     * )
     */
    public function buildSetFilters(array $filterList)
    {
        $dataOut = [];

        foreach ($filterList as $filter) {
            if (self::isUuid($filter)) {
                throw new ApiException('filterprocessor-build-set-filters-uuid-not-supported');
            } elseif (filter_var($filter, FILTER_VALIDATE_URL)) {
                $dataOut[] = ['predicate' => OpenSkos::SET, 'value' => $filter, 'type' => self::TYPE_URI, 'entity' => self::ENTITY_SET];
            } else {
                throw new ApiException('filterprocessor-build-set-filters-search-by-string');
            }
        }

        return $dataOut;
    }
}
