<?php

namespace App\Entity;

use App\Annotation\Document;
use App\Database\Doctrine;
use Doctrine\Common\Annotations\AnnotationReader;
use JsonMapper;

abstract class AbstractEntity
{
    /**
     * @param mixed $data
     */
    public function __construct($data = null)
    {
        if (is_int($data)) {
            $data = ['id' => $data];
        }
        $this->populate($data);
    }

    public static function getTable()
    {
        // Fetch all annotations
        $annotationReader = new AnnotationReader();
        $documentReflection = new \ReflectionClass(static::class);
        $documentAnnotations = $annotationReader->getClassAnnotations($documentReflection);

        // Loop through annotations and return the table
        foreach ($documentAnnotations as $annotation) {
            if ($annotation instanceof Document\Table) {
                return $annotation->value;
            }
        }

        return null;
    }

    /**
     * @param mixed $data
     *
     * @return self
     */
    public function populate($data = null): self
    {
        static $mapper = null;
        if (is_null($mapper)) {
            $mapper = new JsonMapper();
        }

        // Fetch from DB if no data was given
        if (is_null($data)) {
            $stmt = Doctrine::getConnection()
                ->createQueryBuilder()
                ->select('*')
                ->from(static::getTable(), 't')
                ->where('1 = 1');

            /* // Fetch more data from the database */
            /* $stmt = $connection */
            /*     ->createQueryBuilder() */
            /*     ->select('*') */
            /*     ->from($this->annotations['table'], 't') */
            /*     ->where('t.'.($this->annotations['uuid']).' = :uuid') */
            /*     ->setParameter(':uuid', $uri) */
            /*     ->execute(); */
            /* if (is_int($stmt)) { */
            /*     return []; */
            /* } */
            /* $rawDocument = $stmt->fetch(); */

            $searchData = array_filter($this->__toArray());
            foreach ($searchData as $name => $value) {
                $stmt = $stmt->andWhere("t.${name} = :${name}");
                $stmt->setParameter(":${name}", $value);
            }

            $res = $stmt->execute();
            if (is_int($res)) {
                return $this;
            }

            $data = $res->fetch();
        }

        // Normalize data
        if (is_array($data)) {
            $data = json_encode($data);
        }
        if (is_string($data)) {
            $data = json_decode($data);
        }
        if (!($data instanceof \stdClass)) {
            return $this;
        }

        $mapper->map($data, $this);

        return $this;
    }

    /**
     * @return array
     */
    public function __toArray(): array
    {
        return get_object_vars($this);
    }
}
