<?php

declare(strict_types=1);

namespace App\OpenSkos;

final class Pagination
{
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

    public function __construct(
        int $level = 1,
        int $limit = 100,
        int $offset = 0
    ) {
        $this->level = $level;
        $this->offset = $offset;
        $this->limit = $limit;

        if ($level < 1 || $level > 4) {
            throw new InvalidPaginationLevel($level);
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
}
