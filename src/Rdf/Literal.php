<?php

namespace App\Rdf;

class Literal implements RdfTerm
{
    const TYPE_DATETIME = 'http://www.w3.org/2001/XMLSchema#dateTime';
    const TYPE_BOOL = 'http://www.w3.org/2001/XMLSchema#bool';
    const TYPE_STRING = 'http://www.w3.org/2001/XMLSchema#string';

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    private $type;

    /**
     * Literal constructor.
     *
     * TODO: Make language and type parameters be objects or enums?
     * TODO: Or make Typed Literals. Such as StringLiteral, DateTypeLiteral
     *
     * @param string $value
     * @param mixed $language
     * @param mixed $type
     */
    public function __construct(string $value, $language = null, $type = null)
    {
        $this->value = $value;
        $this->language = $language;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Output the literal as string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}
