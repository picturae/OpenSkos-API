<?php

declare(strict_types=1);

namespace App\Rest;

use App\Rdf\Format\RdfFormat;
use App\Rdf\RdfResource;

final class ListResponse implements SkosResponse
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
    /**
     * @var RdfFormat
     */
    private $format;

    public function __construct(
        array $docs,
        int $total,
        int $offset,
        RdfFormat $format
    ) {
        $this->docs = $docs;
        $this->total = $total;
        $this->offset = $offset;
        $this->format = $format;
    }

    /**
     * @return RdfResource[]
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

    /**
     * @return RdfFormat
     */
    public function format(): RdfFormat
    {
        return $this->format;
    }
}
