<?php

namespace App\Exception;

use App\Annotation\Error;

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

    /**
     * @param string|array $errorCode
     *
     * @Error(code="internal-server-error",
     *        status=500,
     *        description="An ApiException was thrown but no error code was given"
     * )
     */
    public function __construct($errorCode, array $data = null)
    {
        if (is_array($errorCode)) {
            $data = $errorCode['data'] ?? null;
            $errorCode = $errorCode['code'] ?? 'internal-server-error';
        }

        $this->errorCode = $errorCode;

        if (!is_null($data)) {
            $this->data = $data;
        }

        parent::__construct($errorCode.': '.json_encode($data));
    }
}
