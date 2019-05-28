<?php

declare(strict_types=1);

namespace App\OpenSkos;

use App\Rdf\RdfHeaders;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ApiRequest
{
    /**
     * @var string
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
     * @param string $format
     * @param int    $level
     * @param int    $limit
     * @param int    $offset
     */
    public function __construct(
        string $format = RdfHeaders::FORMAT_JSON_LD,
        int $level = 1,
        int $limit = 100,
        int $offset = 0
    ) {
        $this->format = $format;
        $this->level = $level;
        $this->offset = $offset;
        $this->limit = $limit;

        if (!in_array($format, [RdfHeaders::FORMAT_JSON_LD, RdfHeaders::FORMAT_RDF_XML])) {
            throw new HttpException(
                406,
                "'$format' is not an accepted format"
            );
        }

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
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @return string
     */
    public function getReturnContentType(): string
    {
        $formatOut = RdfHeaders::CONTENT_TYPE_HEADER_HTML;

        switch ($this->format) {
            case RdfHeaders::FORMAT_JSON_LD:
                $formatOut = RdfHeaders::CONTENT_TYPE_HEADER_JSON_LD;
                break;
            case RdfHeaders::FORMAT_RDF_XML:
                $formatOut = RdfHeaders::CONTENT_TYPE_HEADER_RDF_XML;
                break;
        }

        return $formatOut;
    }
}
