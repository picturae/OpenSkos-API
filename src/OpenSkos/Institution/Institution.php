<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution;

use App\Annotation\Document;
use App\Ontology\Dc;
use App\Ontology\DcTerms;
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
    const modifiedBy                  = 'modifiedBy';
    const creator                     = 'creator';
    const datesubmitted               = 'datesubmitted';
    const modified                    = 'modified';

    /**
     * @var string[]
     */
    protected static $mapping = [
        self::code                        => OpenSkos::CODE,
        self::datesubmitted               => DcTerms::DATE_SUBMITTED,
        self::creator                     => Dc::CREATOR,
        self::modifiedBy                  => OpenSkos::MODIFIED_BY,
        self::modified                    => DcTerms::MODIFIED,
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

    protected static $updateFields = [
        OpenSkos::DISABLE_SEARCH_IN_OTHER_TENANTS,
        OpenSkos::ENABLESKOSXL,
        OpenSkos::ENABLE_STATUSSES_SYSTEM,
        OpenSkos::MODIFIED_BY,
        OpenSkos::NAME,
        OpenSkos::WEBPAGE,
        VCard::ADR,
        VCard::COUNTRY,
        VCard::EMAIL,
        VCard::LOCALITY,
        VCard::ORGUNIT,
        VCard::PCODE,
    ];

    public function __toString()
    {
        $code = $this->getValue(OpenSkos::CODE);
        if (is_null($code)) {
            return '';
        }

        return $code->__toString();
    }
}
