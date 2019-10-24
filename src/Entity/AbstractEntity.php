<?php

namespace App\Entity;

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
}
