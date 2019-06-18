<?php

declare(strict_types=1);

namespace App\OpenSkos;

use App\OpenSkos\Exception\InvalidApiRequestLevel;
use App\Rdf\Format\JsonLd;
use App\Rdf\Format\RdfFormat;

final class ApiRequest
{
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
     * ApiRequest constructor.
     *
     * @param RdfFormat|null $format
     * @param int            $level
     * @param int            $limit
     * @param int            $offset
     */
    public function __construct(
        ?RdfFormat $format = null,
        int $level = 1,
        int $limit = 100,
        int $offset = 0
    ) {
        if (null === $format) {
            $format = JsonLd::instance();
        }

        $this->format = $format;
        $this->level = $level;
        $this->offset = $offset;
        $this->limit = $limit;

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
}
