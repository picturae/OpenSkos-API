<?php

declare(strict_types=1);

namespace App\OpenSkos\Filters;

use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class FilterProcessor
{
    //Filter Types
    const TYPE_URI = 'uri';
    const TYPE_UUID = 'uuid';
    const TYPE_STRING = 'string';

    //Group for filter
    const ENTITY_INSTITUTION = 'institution';
    const ENTITY_SET = 'set';
    const ENTITY_CONCEPTSCHEME = 'conceptscheme';

    private $connection;

    /**
     * FilterProcessor constructor.
     *
     * @param Connection $connection
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
                $dataOut[] = ['predicate' => DcTerms::PUBLISHER, 'value' => $filter, 'type' => self::TYPE_URI, 'entity' => self::ENTITY_INSTITUTION];
            } else {
                $dataOut[] = ['predicate' => OpenSkos::TENANT, 'value' => $filter, 'type' => self::TYPE_STRING, 'entity' => self::ENTITY_INSTITUTION];
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
                $dataOut[] = ['predicate' => OpenSkos::SET, 'value' => $filter, 'type' => self::TYPE_URI, 'entity' => self::ENTITY_SET];
            } else {
                throw new BadRequestHttpException('The search by string for sets could not be retrieved (Predicate is not used in Jena Store).');
            }
        }

        return $dataOut;
    }

    /**
     * @param int   $profile_id
     * @param array $to_apply
     *
     * @return array
     */
    public function retrieveSearchProfile(int $profile_id, array $to_apply)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('searchOptions')
            ->from('search_profiles')
            ->where('id = :id')
            ->setParameter('id', $profile_id);

        $filters = [];
        $res = $qb->execute();

        $profile = $res->fetchAll();
        if (0 === count($profile)) {
            throw new BadRequestHttpException('The searchProfile id does not exist');
        }
        $searchOptions = unserialize($profile[0]['searchOptions']);

        if (isset($to_apply[self::ENTITY_INSTITUTION]) && true === $to_apply[self::ENTITY_INSTITUTION]) {
            if (isset($searchOptions['tenants']) && 0 !== count($searchOptions['tenants'])) {
                $read_filters = $this->buildInstitutionFilters($searchOptions['tenants']);
                $filters = array_merge($filters, $read_filters);
            }
        }
        if (isset($to_apply[self::ENTITY_SET]) && true === $to_apply[self::ENTITY_SET]) {
            if (isset($searchOptions['collections']) && 0 !== count($searchOptions['collections'])) {
                $read_filters = $this->buildSetFilters($searchOptions['collections']);
                $filters = array_merge($filters, $read_filters);
            }
        }

        return $filters;
    }
}
