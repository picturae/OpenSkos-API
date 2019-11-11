<?php

namespace App\Exception;

class ApiException extends \Exception
{
    /**
     * @var int
     */
    public $status = 500;

    /**
     * @var string
     */
    public $errorCode = 'unknown-error';

    /**
     * @var array
     */
    public $data;

    public function __construct(string $errorCode, array $data = null)
    {
        $this->errorCode = $errorCode;

        if (!is_null($data)) {
            $this->data = $data;
        }

        parent::__construct($errorCode.': '.json_encode($data));
    }
}
