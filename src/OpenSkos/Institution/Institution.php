<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution;

use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\VCard;
use App\OpenSkos\SkosResource;
use App\Rdf\Iri;

final class Institution extends SkosResource
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
    protected static $mapping = [
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

        $this->literals = array_fill_keys(
            array_values(self::$mapping),
            null
        );
    }
}
