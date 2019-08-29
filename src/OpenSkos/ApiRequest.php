<?php

declare(strict_types=1);

namespace App\OpenSkos;

use App\OpenSkos\Exception\InvalidApiRequestLevel;
use App\Rdf\Format\JsonLd;
use App\Rdf\Format\RdfFormat;

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
     * @var int
     */
    private $searchProfile;

    /**
     * ApiRequest constructor.
     *
     * @param RdfFormat|null $format
     * @param int            $level
     * @param int            $limit
     * @param int            $offset
     * @param array          $institutions
     * @param array          $sets
     * @param int            $searchProfile
     */
    public function __construct(
        array $allParams = [],
        ?RdfFormat $format = null,
        int $level = 1,
        int $limit = 100,
        int $offset = 0,
        array $institutions = [],
        array $sets = [],
        int $searchProfile = 0
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
        $this->searchProfile = $searchProfile;

        if ($level < 1 || $level > 4) {
            throw new InvalidApiRequestLevel($level);
        }
        if ($limit < 0 || $offset < 0) {
            throw new \InvalidArgumentException(
                "Limit and offset must be zero or higher. $limit and $offset passed"
            );
        }
    }

    /**
     * @return mixed
     */
    public function getParameter($key)
    {
        return $this->allParams[$key] ?? null;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return RdfFormat
     */
    public function getFormat(): RdfFormat
    {
        return $this->format;
    }

    /**
     * @return array
     */
    public function getInstitutions(): array
    {
        return $this->institutions;
    }

    /**
     * @param array $institutions
     */
    public function setInstitutions(array $institutions): void
    {
        $this->institutions = $institutions;
    }

    /**
     * @return array
     */
    public function getSets(): array
    {
        return $this->sets;
    }

    /**
     * @param array $sets
     */
    public function setSets(array $sets): void
    {
        $this->sets = $sets;
    }

    /**
     * @return int
     */
    public function getSearchProfile(): int
    {
        return $this->searchProfile;
    }

    /**
     * @param int $searchProfile
     */
    public function setSearchProfile(int $searchProfile): void
    {
        $this->searchProfile = $searchProfile;
    }
}
