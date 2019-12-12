<?php

declare(strict_types=1);

namespace App\OpenSkos;

use App\Annotation\Error;
use App\Exception\ApiException;
use App\Rdf\Format\JsonLd;
use App\Rdf\Format\RdfFormat;
use App\Security\Authentication;
use EasyRdf_Graph as Graph;

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
     * @var ?string
     */
    private $foreignUri;

    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var Graph
     */
    private $graph;

    /**
     * ApiRequest constructor.
     *
     * @Error(code="apirequest-invalid-level",
     *        status=400,
     *        description="An invalid level was requested",
     *        fields={"level"}
     * )
     * @Error(code="apirequest-invalid-limit",
     *        status=400,
     *        description="An invalid limit parameter was given, it needs to be >=0",
     *        fields={"limit"}
     * )
     * @Error(code="apirequest-invalid-offset",
     *        status=400,
     *        description="An invalid offset parameter was given, it needs to be >=0",
     *        fields={"offset"}
     * )
     */
    public function __construct(
        array $allParams = [],
        ?RdfFormat $format = null,
        int $level = 1,
        int $limit = 100,
        int $offset = 0,
        array $institutions = [],
        array $sets = [],
        string $foreignUri = null,
        Authentication $authentication = null,
        Graph $graph = null
    ) {
        if (null === $format) {
            $format = JsonLd::instance();
        }

        $this->allParams    = $allParams;
        $this->format       = $format;
        $this->level        = $level;
        $this->offset       = $offset;
        $this->limit        = $limit;
        $this->institutions = $institutions;
        $this->sets         = $sets;
        $this->foreignUri   = $foreignUri;

        if (is_null($authentication)) {
            $this->authentication = new Authentication();
        } else {
            $this->authentication = $authentication;
        }

        if (is_null($graph)) {
            $this->graph = new Graph();
        } else {
            $this->graph = $graph;
        }

        if ($level < 1 || $level > 4) {
            throw new ApiException('apirequest-invalid-level', [
                'level' => $level,
            ]);
        }
        if ($limit < 0) {
            throw new ApiException('apirequest-invalid-limit', [
                'limit' => $limit,
            ]);
        }
        if ($offset < 0) {
            throw new ApiException('apirequest-invalid-offset', [
                'offset' => $offset,
            ]);
        }
    }

    /**
     * @return mixed
     */
    public function getParameter(string $key, ?string $default = null)
    {
        return $this->allParams[$key] ?? $default;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getFormat(): RdfFormat
    {
        return $this->format;
    }

    public function getInstitutions(): array
    {
        return $this->institutions;
    }

    public function setInstitutions(array $institutions): void
    {
        $this->institutions = $institutions;
    }

    public function getSets(): array
    {
        return $this->sets;
    }

    public function setSets(array $sets): void
    {
        $this->sets = $sets;
    }

    /**
     * @return mixed
     */
    public function getForeignUri()
    {
        return $this->foreignUri;
    }

    /**
     * @return Authentication
     */
    public function getAuthentication()
    {
        return $this->authentication;
    }

    /**
     * @return Graph
     */
    public function getGraph()
    {
        return $this->graph;
    }
}
