<?php

declare(strict_types=1);

namespace App\Rest;

/**
 * Class ScalarResponse.
 */
final class ScalarResponse
{
    /**
     * @var array
     */
    private $docs;

    /**
     * ScalarResponse constructor.
     *
     * @param array $docs
     */
    public function __construct(
        array $docs
    ) {
        $this->docs = $docs;
    }

    /**
     * @return array
     */
    public function getDocs(): array
    {
        return $this->docs;
    }
}
