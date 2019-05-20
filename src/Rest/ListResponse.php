<?php

declare(strict_types=1);

namespace App\Rest;

final class ListResponse
{
    /**
     * @var array
     */
    private $docs;

    /**
     * @var int
     */
    private $total;

    /**
     * @var int
     */
    private $offset;

    public function __construct(
        array $docs,
        int $total,
        int $offset
    ) {
        $this->docs = $docs;
        $this->total = $total;
        $this->offset = $offset;
    }

    /**
     * @return array
     */
    public function getDocs(): array
    {
        return $this->docs;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }
}
