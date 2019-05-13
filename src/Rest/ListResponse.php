<?php declare(strict_types=1);

namespace App\Rest;

use Symfony\Component\HttpFoundation\Response;

final class ListResponse {
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
    )
    {
        $this->docs = $docs;
        $this->total = $total;
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param int $total
     */
    public function setTotal(int $total)
    {
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset(int $offset)
    {
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
     * @param array $docs
     */
    public function setDocs(array $docs)
    {
        $this->docs = $docs;
    }

    /**
     * TODO: Replace with Transformers for specific formats
     * TODO: Look at https://symfony.com/doc/current/components/serializer.html
     * @return array
     */
    public function toArray() : array {
        return [
            'docs' => $this->docs,
            'total' => $this->total,
            'offset' => $this->offset,
        ];
    }
}