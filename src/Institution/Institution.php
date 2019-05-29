<?php

declare(strict_types=1);

namespace App\Institution;

use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\VCard;
use App\Rdf\Iri;
use App\Rdf\Literal;
use App\Rdf\Triple;
use App\Rdf\TripleSet;

final class Institution implements TripleSet
{
    const code = 'code';
    const name = 'name';
    const organisationUnit = 'organisationUnit';
    const email = 'email';
    const website = 'website';
    const streetAddress = 'streetAddress';
    const locality = 'locality';
    const postalCode = 'postalCode';
    const countryName = 'countryName';
    const enableStatusesSystem = 'enableStatusesSystem';
    const enableSkosXl = 'enableSkosXl';
    const type = 'type';
    const disableSearchInOtherTenants = 'disableSearchInOtherTenants';

    /**
     * @var string[]
     */
    private static $mapping = [
        self::code => OpenSkos::CODE,
        self::name => OpenSkos::NAME,
        self::disableSearchInOtherTenants => OpenSkos::DISABLESEARCHINOTERTENANTS,
        self::organisationUnit => VCard::ORGUNIT,
        self::email => VCard::EMAIL,
        self::website => OpenSkos::WEBPAGE,
        self::streetAddress => VCard::ADR,
        self::locality => VCard::LOCALITY,
        self::postalCode => VCard::PCODE,
        self::countryName => VCard::COUNTRY,
        self::enableStatusesSystem => OpenSkos::ENABLESTATUSSESSYSTEMS,
        self::enableSkosXl => OpenSkos::ENABLESKOSXL,
        self::type => Rdf::TYPE,
    ];

    /**
     * @var Iri
     */
    private $subject;

    /**
     * @var Triple[]
     */
    private $triples = [];

    /**
     * @var Literal[]
     */
    private $properties = [];

    public function __construct(Iri $subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string[]
     */
    public static function getMapping(): array
    {
        return self::$mapping;
    }

    //TODO: Generate getters.

    /**
     * @return Iri
     */
    public function getSubject(): Iri
    {
        return $this->subject;
    }

    /**
     * @return Literal|null
     */
    public function getCode(): ?Literal
    {
        return $this->properties[self::code] ?? null;
    }

    /**
     * @return Literal|null
     */
    public function getWebsite(): ?Literal
    {
        return $this->properties[self::website] ?? null;
    }

    public function count(): int
    {
        return count($this->properties);
    }

    /**
     * @param Iri      $subject
     * @param Triple[] $triples
     *
     * @return Institution
     */
    public static function fromTriples(Iri $subject, array $triples): Institution
    {
        $invMapping = array_flip(self::$mapping);
        $properties = [];
        foreach ($triples as $triple) {
            // Skip unrelated triples
            if ($triple->getSubject()->getUri() !== $subject->getUri()) {
                continue;
            }

            $property = $invMapping[$triple->getPredicate()->getUri()] ?? null;
            // Skip unknown properties
            if (null === $property) {
                continue;
            }

            $object = $triple->getObject();
            /*
            // Skip non-properties. TODO: throw an exception?
            if (!$object instanceof Literal) {
                continue;
            }
            */
            $properties[$property] = $object;
        }

        $obj = new self($subject);
        $obj->properties = $properties;

        return $obj;
    }

    /**
     * @return Literal[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return Triple[]
     */
    public function triples(): array
    {
        return $this->triples;
    }
}
