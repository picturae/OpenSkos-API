<?php

declare(strict_types=1);

namespace App\Rest;

use App\Rdf\Format\RdfFormat;

final class DirectGraphResponse implements SkosResponse
{
    /**
     * @var \EasyRdf_Graph
     */
    private $graph;

    /**
     * @var RdfFormat
     */
    private $format;

    public function __construct(
        \EasyRdf_Graph $graph,
        RdfFormat $format
    ) {
        $this->graph = $graph;
        $this->format = $format;
    }

    public function getGraph(): \EasyRdf_Graph
    {
        return $this->graph;
    }

    public function format(): RdfFormat
    {
        return $this->format;
    }
}
