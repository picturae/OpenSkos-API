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
     * @var string
     */
    public $description = '';

    /**
     * @var array
     */
    public $data = [];

    /**
     * @var array
     */
    public $fields = [];

    /**
     * @var array
     */
    public $config = [];

    protected static function knownErrors(): array
    {
        static $known = null;

        if (is_null($known)) {
            $known = json_decode(file_get_contents(__DIR__.'/list.json'), true);
        }

        return $known;
    }

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
            $data      = $errorCode['data'] ?? null;
            $errorCode = $errorCode['code'] ?? 'internal-server-error';
        }

        $this->errorCode = $errorCode;

        parent::__construct($errorCode.': '.json_encode($data));

        $knownErrors = static::knownErrors();
        if (isset($knownErrors[$errorCode])) {
            $this->config = $knownErrors[$errorCode];
        }

        if (isset($this->config['status'])) {
            $this->status = $this->config['status'];
        }

        if (isset($this->config['description'])) {
            $this->description = $this->config['description'];
        }

        if (isset($this->config['fields'])) {
            $this->fields = $this->config['fields'];
        }

        if (!is_null($data)) {
            foreach ($this->fields as $field) {
                if (isset($data[$field])) {
                    $this->data[$field] = $data[$field];
                }
            }
        }
    }
}
