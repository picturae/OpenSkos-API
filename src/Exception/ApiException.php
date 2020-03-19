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

        // Fetch error config
        $knownErrors = static::knownErrors();
        if (isset($knownErrors[$errorCode])) {
            $this->config = $knownErrors[$errorCode];
        }

        // Fetch hardwired status
        if (isset($this->config['status'])) {
            $this->status = $this->config['status'];
        }

        // Fetch hardwired description
        if (isset($this->config['description'])) {
            $this->description = $this->config['description'];
        }

        // Fetch hardwired field list
        if (isset($this->config['fields'])) {
            $this->fields = $this->config['fields'];
        }

        if (is_array($data)) {
            if (isset($data['message'])) {
                $this->message = $data['message'];
            }
            foreach ($this->fields as $field) {
                if (isset($data[$field])) {
                    if (is_object($data[$field]) && method_exists($data[$field], '__toString')) {
                        $this->data[$field] = $data[$field]->__toString();
                    } else {
                        $this->data[$field] = $data[$field];
                    }
                }
            }
        }
    }
}
