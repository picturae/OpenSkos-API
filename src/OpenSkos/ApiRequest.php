<?php

declare(strict_types=1);

namespace App\OpenSkos;

use App\OpenSkos\Exception\InvalidApiRequestLevel;
use App\Rdf\Format\JsonLd;
use App\Rdf\Format\RdfFormat;
use App\Security\Authentication;

final class ApiRequest
{
    /**
     * @var array
     */
    private $allParams;

    /**
     * @var RdfFormat
     */
    private $format;

    /**
     * @var int
     */
    private $level;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var array
     */
    private $institutions;

    /**
     * @var array
     */
    private $sets;

    /**
     * @var ?string
     */
    private $foreignUri;

    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * ApiRequest constructor.
     */
    public function __construct(
        array $allParams = [],
        ?RdfFormat $format = null,
        int $level = 1,
        int $limit = 100,
        int $offset = 0,
        array $institutions = [],
        array $sets = [],
        string $foreignUri = null,
        Authentication $authentication = null
    ) {
        if (null === $format) {
            $format = JsonLd::instance();
        }

        $this->allParams = $allParams;
        $this->format = $format;
        $this->level = $level;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->institutions = $institutions;
        $this->sets = $sets;
        $this->foreignUri = $foreignUri;

        if (is_null($authentication)) {
            $this->authentication = new Authentication();
        } else {
            $this->authentication = $authentication;
        }

        if ($level < 1 || $level > 4) {
            throw new InvalidApiRequestLevel($level);
        }
        if ($limit < 0 || $offset < 0) {
            throw new \InvalidArgumentException("Limit and offset must be zero or higher. $limit and $offset passed");
        }
    }

    /**
     * @return mixed
     */
    public function getParameter(string $key, ?string $default = null)
    {
        return $this->allParams[$key] ?? $default;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getFormat(): RdfFormat
    {
        return $this->format;
    }

    public function getInstitutions(): array
    {
        return $this->institutions;
    }

    public function setInstitutions(array $institutions): void
    {
        $this->institutions = $institutions;
    }

    public function getSets(): array
    {
        return $this->sets;
    }

    public function setSets(array $sets): void
    {
        $this->sets = $sets;
    }

    /**
     * @return mixed
     */
    public function getForeignUri()
    {
        return $this->foreignUri;
    }

    /**
     * @return Authentication
     */
    public function getAuthentication()
    {
        return $this->authentication;
    }
}
