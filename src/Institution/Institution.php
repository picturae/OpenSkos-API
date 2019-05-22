<?php

declare(strict_types=1);

namespace App\Institution;

use App\Ontology\OpenSkos;
use App\Ontology\VCard;
use App\Rdf\Iri;
use App\Rdf\Literal;
use App\Rdf\Triple;

final class Institution
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

    /**
     * @var string[]
     */
    private static $mapping = [
        self::code => OpenSkos::CODE,
        self::name => OpenSkos::NAME,
        self::organisationUnit => VCard::ORGUNIT,
        self::email => VCard::EMAIL,
        self::website => OpenSkos::WEBPAGE,
        self::streetAddress => VCard::ADR,
        self::locality => VCard::LOCALITY,
        self::postalCode => VCard::PCODE,
        self::countryName => VCard::COUNTRY,
        self::enableStatusesSystem => OpenSkos::ENABLESTATUSSESSYSTEMS,
        self::enableSkosXl => OpenSkos::ENABLESKOSXL,
    ];

    /**
     * @var Iri
     */
    private $subject;

    /**
     * @var Literal[]
     */
    private $literals = [];

    public function __construct(Iri $subject)
    {
        $this->subject = $subject;
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
        return $this->literals[self::code] ?? null;
    }

    /**
     * @return Literal|null
     */
    public function getWebsite(): ?Literal
    {
        return $this->literals[self::website] ?? null;
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
        $literals = [];
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
            // Skip non-literals. TODO: throw an exception?
            if (!$object instanceof Literal) {
                continue;
            }
            $literals[$property] = $object;

            /* TODO: Add Resource when needed
                if (!$object instanceof Iri) {
                    continue;
                }
                $resources[$property] = $object;
             */
        }

        $obj = new self($subject);
        $obj->literals = $literals;

        return $obj;
    }
}
