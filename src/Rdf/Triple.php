<?php

declare(strict_types=1);

namespace App\Rdf;

use App\Annotation\Error;
use App\Exception\ApiException;
use App\Rdf\Literal\BooleanLiteral;
use App\Rdf\Literal\DatetimeLiteral;
use App\Rdf\Literal\Literal;
use App\Rdf\Literal\StringLiteral;

final class Triple
{
    /**
     * @var Iri
     */
    private $subject;
    /**
     * @var Iri
     */
    private $predicate;
    /**
     * @var RdfTerm
     */
    private $object;

    /**
     * @Error(code="rdf-triple-construct-invalid-object-type",
     *        status=500,
     *        description="Object must be either Iri or Literal",
     *        fields={"received"}
     * )
     */
    public function __construct(
        Iri $subject,
        Iri $predicate,
        RdfTerm $object
    ) {
        $this->subject   = $subject;
        $this->predicate = $predicate;
        $this->object    = $object;

        if (!($object instanceof Iri || $object instanceof Literal)) {
            $type = gettype($this->object);
            if ('object' == $type) {
                $type .= '('.get_class($this->object).')';
            }
            throw new ApiException('rdf-triple-construct-invalid-object-type', [
                'received' => $type,
            ]);
        }
    }

    public function getSubject(): Iri
    {
        return $this->subject;
    }

    public function setSubject(Iri $subject): Triple
    {
        $this->subject = $subject;

        return $this;
    }

    public function getPredicate(): Iri
    {
        return $this->predicate;
    }

    public function getObject(): RdfTerm
    {
        return $this->object;
    }

    public static function fromString(string $tripleString): ?Triple
    {
        if (!strlen($tripleString)) {
            return null;
        }

        // Build the regex
        $iri   = '[a-z\\:\\/0-9\\-\\.#@]+';
        $lit   = $iri;
        $lang  = '[a-z]{2}';
        $regex = '/'.
            '<(?<subject>'.$iri.')>'.
            ' '.
            '<(?<predicate>'.$iri.')>'.
            ' '.
            '('.
                '"(?<literal>'.$lit.')"'.
                '(@(?<language>'.$lang.'))?'.
                '(\\^\\^<(?<literalType>'.$iri.')>)?'.
            '|'.
                '<(?<object>'.$iri.')>'.
            ')'.
            '( \\.)?'.
            '/i';

        // Run the regex
        if (!preg_match($regex, $tripleString, $matches)) {
            return null;
        }

        $subject   = new Iri($matches['subject']);
        $predicate = new Iri($matches['predicate']);

        $literal     = strlen($matches['literal']) ? $matches['literal'] : null;
        $language    = strlen($matches['language']) ? $matches['language'] : null;
        $literalType = strlen($matches['literalType']) ? $matches['literalType'] : 'http://www.w3.org/2001/XMLSchema#string';
        $object      = strlen($matches['object']) ? $matches['object'] : null;

        if ($object) {
            return new static($subject, $predicate, new Iri($object));
        }

        if (is_null($literal)) {
            return null;
        }

        switch ($literalType) {
            case BooleanLiteral::typeIri()->getUri():
                return new static($subject, $predicate, BooleanLiteral::fromString($literal));
            case DatetimeLiteral::typeIri()->getUri():
                return new static($subject, $predicate, new DatetimeLiteral(new \DateTime($literal)));
            case StringLiteral::typeIri()->getUri():
                return new static($subject, $predicate, new StringLiteral($literal, $language));
        }

        return null;
    }

    /**
     * @Error(code="rdf-triple-tostring-invalid-object-type",
     *        status=500,
     *        description="Object must be either Iri or Literal",
     *        fields={"received"}
     * )
     */
    public function __toString(): string
    {
        if ($this->object instanceof Iri) {
            $retVal = sprintf(
                '<%s> <%s> <%s>',
                $this->subject->getUri(),
                $this->predicate->getUri(),
                $this->object->getUri()
            );

            return $retVal;
        } elseif ($this->object instanceof Literal) {
            $hasLang = method_exists($this->object, 'lang') && $this->object->lang();

            return sprintf(
                '<%s> <%s> "%s"%s%s',
                $this->subject->getUri(),
                $this->predicate->getUri(),
                $this->object->__toString(),
                $hasLang ? '@'.$this->object->lang() : '',
                $hasLang ? '' : ('^^'.$this->object->typeIri()->ntripleString())
            );
        }

        $type = gettype($this->object);
        if ('object' == $type) {
            $type .= '('.get_class($this->object).')';
        }

        throw new ApiException('rdf-triple-tostring-invalid-object-type', [
            'received' => $type,
        ]);
    }
}
