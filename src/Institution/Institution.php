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

final class Institution extends TripleSet
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


    /**
     * @return string[]
     */
    public function getLevel2Predicate()
    {
        //Institutions have no level 2
        return '';
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


}
