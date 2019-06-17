<?php

declare(strict_types=1);

namespace App\Rest;

use App\Rdf\Format\RdfFormat;
use App\Rdf\RdfResource;

/**
 * Class ScalarResponse.
 */
final class ScalarResponse implements SkosResponse
{
    /**
     * @var RdfResource
     */
    private $doc;

    /**
     * @var RdfFormat
     */
    private $format;

    /**
     * ScalarResponse constructor.
     *
     * @param RdfResource $doc
     * @param RdfFormat   $format
     */
    public function __construct(
        RdfResource $doc, RdfFormat $format
    ) {
        $this->doc = $doc;
        $this->format = $format;
    }

    /**
     * @return RdfResource
     */
    public function doc(): RdfResource
    {
        return $this->doc;
    }

    public function format(): RdfFormat
    {
        return $this->format;
    }
}
