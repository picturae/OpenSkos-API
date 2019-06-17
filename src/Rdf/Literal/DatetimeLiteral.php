<?php

declare(strict_types=1);

namespace App\Rdf\Literal;

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
     * @param string $value
     *
     * @return DatetimeLiteral
     */
    public static function fromString(string $value): self
    {
        try {
            return new self(new \DateTime($value));
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                "Value '$value' can be parsed to DateTimeLiteral",
                0,
                $e
            );
        }
    }
}
