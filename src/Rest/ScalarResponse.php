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
     * @param $doc
     */
    public function __construct(
        $doc
    ) {
        $this->docs = [$doc];
    }

    /**
     * @return array
     */
    public function getDocs(): array
    {
        return $this->docs;
    }
}
