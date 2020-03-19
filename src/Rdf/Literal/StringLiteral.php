<?php

declare(strict_types=1);

namespace App\Rdf\Literal;

use App\Rdf\Iri;

final class StringLiteral implements Literal
{
    /**
     * @var string
     */
    private $value;
    /**
     * @var string|null
     */
    private $lang;

    /**
     * StringLiteral constructor.
     */
    public function __construct(string $value, ?string $lang = null)
    {
        $this->value = $value;
        $this->lang  = $lang;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function lang(): ?string
    {
        return $this->lang;
    }

    /**
     * @ErrorInherit(class=Iri::class , method="__construct")
     */
    public static function typeIri(): Iri
    {
        return new Iri('http://www.w3.org/2001/XMLSchema#string');
    }

    /**
     * Output the object as string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}
