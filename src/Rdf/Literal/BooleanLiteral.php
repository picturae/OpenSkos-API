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
        return new Iri('http://www.w3.org/2001/XMLSchema#bool');
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
     * @param string $value
     *
     * @return BooleanLiteral
     */
    public static function fromString(string $value): self
    {
        switch ($value) {
            case 'true': $bool = true; break;
            case 'false': $bool = false; break;
            default: throw new \InvalidArgumentException("Cant parse bool value: '$value'");
        }

        return new BooleanLiteral($bool);
    }
}
