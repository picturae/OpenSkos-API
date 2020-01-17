<?php

declare(strict_types=1);

namespace App\Rdf\Literal;

use App\Rdf\Iri;

final class IntegerLiteral implements Literal
{
    /**
     * @var int
     */
    private $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }

    /**
     * @ErrorInherit(class=Iri::class , method="__construct")
     */
    public static function typeIri(): Iri
    {
        return new Iri('http://www.w3.org/2001/XMLSchema#integer');
    }

    /**
     * Output the object as string.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return IntegerLiteral
     *
     * @ErrorInherit(class=IntegerLiteral::class , method="__construct")
     */
    public static function fromString($value): self
    {
        return new self(intval($value));
    }
}
