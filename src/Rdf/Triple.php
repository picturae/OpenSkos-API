<?php

declare(strict_types=1);

namespace App\Rdf;

use App\Rdf\Literal\Literal;

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

    public function __construct(
        Iri $subject,
        Iri $predicate,
        RdfTerm $object
    ) {
        $this->subject = $subject;
        $this->predicate = $predicate;
        $this->object = $object;

        if (!($object instanceof Iri || $object instanceof Literal)) {
            throw new \InvalidArgumentException('Object must be Iri|Literal got: '.get_class($object));
        }
    }

    /**
     * @return Iri
     */
    public function getSubject(): Iri
    {
        return $this->subject;
    }

    /**
     * @return Iri
     */
    public function getPredicate(): Iri
    {
        return $this->predicate;
    }

    /**
     * @return RdfTerm
     */
    public function getObject(): RdfTerm
    {
        return $this->object;
    }

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
            return sprintf(
                '<%s> <%s> %s',
                $this->subject->getUri(),
                $this->predicate->getUri(),
                $this->object->__toString()
            );
        }

        throw new \LogicException('Object must be either Iri or Literal');
    }
}
