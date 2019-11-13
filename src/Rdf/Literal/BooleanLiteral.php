<?php

declare(strict_types=1);

namespace App\Rdf\Literal;

use App\Rdf\Iri;

final class BooleanLiteral implements Literal
{
    /**
     * @var bool
     */
    private $value;

    public function __construct(bool $value)
    {
        $this->value = $value;
    }

    public function value(): bool
    {
        return $this->value;
    }

    public static function typeIri(): Iri
    {
        return new Iri('http://www.w3.org/2001/XMLSchema#boolean');
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
     * @return BooleanLiteral
     */
    public static function fromString(string $value): self
    {
        $retval = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return new BooleanLiteral($retval);
    }
}
