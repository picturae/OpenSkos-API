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
}
