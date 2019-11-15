<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution;

use App\Annotation\Document;
use App\Ontology\OpenSkos;
use App\Ontology\Org;
use App\Ontology\Rdf;
use App\Ontology\VCard;
use App\Rdf\AbstractRdfDocument;

/**
 * @Document\Type(Org::FORMAL_ORGANIZATION)
 */
final class Institution extends AbstractRdfDocument
{
    const code                        = 'code';
    const name                        = 'name';
    const organisationUnit            = 'organisationUnit';
    const email                       = 'email';
    const website                     = 'website';
    const streetAddress               = 'streetAddress';
    const locality                    = 'locality';
    const postalCode                  = 'postalCode';
    const countryName                 = 'countryName';
    const enableStatusesSystem        = 'enableStatusesSystem';
    const enableSkosXl                = 'enableSkosXl';
    const type                        = 'type';
    const uuid                        = 'uuid';
    const disableSearchInOtherTenants = 'disableSearchInOtherTenants';

    /**
     * @var string[]
     */
    protected static $mapping = [
        self::code                        => OpenSkos::CODE,
        self::name                        => OpenSkos::NAME,
        self::disableSearchInOtherTenants => OpenSkos::DISABLE_SEARCH_IN_OTHER_TENANTS,
        self::organisationUnit            => VCard::ORGUNIT,
        self::email                       => VCard::EMAIL,
        self::website                     => OpenSkos::WEBPAGE,
        self::streetAddress               => VCard::ADR,
        self::locality                    => VCard::LOCALITY,
        self::postalCode                  => VCard::PCODE,
        self::countryName                 => VCard::COUNTRY,
        self::enableStatusesSystem        => OpenSkos::ENABLE_STATUSSES_SYSTEM,
        self::enableSkosXl                => OpenSkos::ENABLESKOSXL,
        self::type                        => Rdf::TYPE,
        self::uuid                        => OpenSkos::UUID,
    ];

    protected static $required = [
        OpenSkos::CODE,
        OpenSkos::NAME,
        OpenSkos::UUID,
    ];
}
