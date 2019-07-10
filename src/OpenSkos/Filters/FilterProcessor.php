<?php

declare(strict_types=1);

namespace App\OpenSkos\Filters;

use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class FilterProcessor
{
    const TYPE_URI = 'uri';
    const TYPE_UUID = 'uuid';
    const TYPE_STRING = 'string';

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
     * @param array $filters
     *
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
     * @param array $filterList
     *
     * @return array
     */
    public function buildInstitutionFilters(array $filterList)
    {
        $dataOut = [];

        foreach ($filterList as $filter) {
            if (self::isUuid($filter)) {
                throw new BadRequestHttpException('The search by UUID for institutions could not be retrieved (Predicate is not used in Jena Store).');
            } elseif (filter_var($filter, FILTER_VALIDATE_URL)) {
                $dataOut[] = ['predicate' => DcTerms::PUBLISHER, 'value' => $filter, 'type' => self::TYPE_URI];
            } else {
                $dataOut[] = ['predicate' => OpenSkos::TENANT, 'value' => $filter, 'type' => self::TYPE_STRING];
            }
        }

        return $dataOut;
    }

    /**
     * @param array $filterList
     *
     * @return array
     */
    public function buildSetFilters(array $filterList)
    {
        $dataOut = [];

        foreach ($filterList as $filter) {
            if (self::isUuid($filter)) {
                throw new BadRequestHttpException('The search by UUID for sets could not be retrieved (Predicate is not used in Jena Store).');
            } elseif (filter_var($filter, FILTER_VALIDATE_URL)) {
                $dataOut[] = ['predicate' => OpenSkos::SET, 'value' => $filter, 'type' => self::TYPE_URI];
            } else {
                throw new BadRequestHttpException('The search by string for sets could not be retrieved (Predicate is not used in Jena Store).');
            }
        }

        return $dataOut;
    }
}
