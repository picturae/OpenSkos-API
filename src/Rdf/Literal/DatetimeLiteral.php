<?php

declare(strict_types=1);

namespace App\Rdf\Literal;

use App\Annotation\Error;
use App\Exception\ApiException;
use App\Rdf\Iri;

final class DatetimeLiteral implements Literal
{
    /**
     * @var \DateTime
     */
    private $dateTime;

    public function __construct(
        \DateTime $dateTime
    ) {
        $this->dateTime = $dateTime;
    }

    public function value(): \DateTime
    {
        return $this->dateTime;
    }

    public static function typeIri(): Iri
    {
        return new Iri('http://www.w3.org/2001/XMLSchema#dateTime');
    }

    /**
     * Output the object as string.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->dateTime->format('c');
    }

    /**
     * @return DatetimeLiteral
     *
     * @Error(code="rdf-literal-datetime-unparsable-value",
     *        status=500,
     *        description="The given value can not be parsed to a DateTimeLiteral",
     *        fields={"value"}
     * )
     */
    public static function fromString(string $value): self
    {
        try {
            return new self(new \DateTime($value));
        } catch (\Exception $e) {
            throw new ApiException('rdf-literal-datetime-unparsable-value', [
                'value' => $value,
            ]);
        }
    }
}
