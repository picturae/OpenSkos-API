<?php

namespace App\Entity;

use App\Annotation\Document;
use App\Annotation\ErrorInherit;
use App\Database\Doctrine;
use Doctrine\Common\Annotations\AnnotationReader;
use JsonMapper;

/**
 * Produces an object with the data directly assigned to it
 * This type of entity DOES NOT contain any triples.
 */
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

    /**
     * Checks the annotations of our class for App\Annotation\Document\Table
     * Returns it's value if it's present, null otherwise.
     */
    public static function getTable(): ?string
    {
        // Fetch all annotations
        $annotationReader    = new AnnotationReader();
        $documentReflection  = new \ReflectionClass(static::class);
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
     * Writes the given data into the entity
     * If no data was given, the data already in the entity is used to query the database.
     *
     * @param mixed $data
     *
     * @ErrorInherit(class=AbstractEntity::class, method="__toArray")
     * @ErrorInherit(class=AbstractEntity::class, method="getTable" )
     */
    public function populate($data = null): self
    {
        static $mapper = null;
        if (is_null($mapper)) {
            $mapper = new JsonMapper();
        }

        // Fetch from DB if no data was given
        if (is_null($data)) {
            // No table = no query
            $table = static::getTable();
            if (is_null($table)) {
                return $this;
            }

            $stmt = Doctrine::getConnection()
                ->createQueryBuilder()
                ->select('*')
                ->from($table, 't')
                ->where('1 = 1');

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

            // Extra checking, the database may be case-insensitive
            foreach ($searchData as $key => $value) {
                if ($searchData[$key] !== $data[$key]) {
                    return $this;
                }
            }
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
     * Turns the entity into an array, containing data only.
     */
    public function __toArray(): array
    {
        return get_object_vars($this);
    }
}
