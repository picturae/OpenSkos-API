<?php

/* * * * * * * * * * * * * * *\
 * CAUTION: GENERATED CLASS  *
\* * * * * * * * * * * * * * */

/**
 * OpenSKOS.
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 *
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

namespace App\Ontology;

use App\Annotation\Error;
use App\Rdf\Iri;
use App\Rdf\Literal\Literal;

final class DcTerms
{
    const NAME_SPACE             = 'http://purl.org/dc/terms/';
    const ABSTRACT               = 'http://purl.org/dc/terms/abstract';
    const ACCESS_RIGHTS          = 'http://purl.org/dc/terms/accessRights';
    const ACCRUAL_METHOD         = 'http://purl.org/dc/terms/accrualMethod';
    const ACCRUAL_PERIODICITY    = 'http://purl.org/dc/terms/accrualPeriodicity';
    const ACCRUAL_POLICY         = 'http://purl.org/dc/terms/accrualPolicy';
    const ALTERNATIVE            = 'http://purl.org/dc/terms/alternative';
    const AUDIENCE               = 'http://purl.org/dc/terms/audience';
    const AVAILABLE              = 'http://purl.org/dc/terms/available';
    const BIBLIOGRAPHIC_CITATION = 'http://purl.org/dc/terms/bibliographicCitation';
    const CONFORMS_TO            = 'http://purl.org/dc/terms/conformsTo';
    const CONTRIBUTOR            = 'http://purl.org/dc/terms/contributor';
    const COVERAGE               = 'http://purl.org/dc/terms/coverage';
    const CREATED                = 'http://purl.org/dc/terms/created';
    const CREATOR                = 'http://purl.org/dc/terms/creator';
    const DATE                   = 'http://purl.org/dc/terms/date';
    const DATE_ACCEPTED          = 'http://purl.org/dc/terms/dateAccepted';
    const DATE_APPROVED          = 'http://purl.org/dc/terms/dateApproved';
    const DATE_COPYRIGHTED       = 'http://purl.org/dc/terms/dateCopyrighted';
    const DATE_SUBMITTED         = 'http://purl.org/dc/terms/dateSubmitted';
    const DESCRIPTION            = 'http://purl.org/dc/terms/description';
    const EDUCATION_LEVEL        = 'http://purl.org/dc/terms/educationLevel';
    const EXTENT                 = 'http://purl.org/dc/terms/extent';
    const FORMAT                 = 'http://purl.org/dc/terms/format';
    const HAS_FORMAT             = 'http://purl.org/dc/terms/hasFormat';
    const HAS_PART               = 'http://purl.org/dc/terms/hasPart';
    const HAS_VERSION            = 'http://purl.org/dc/terms/hasVersion';
    const IDENTIFIER             = 'http://purl.org/dc/terms/identifier';
    const INSTRUCTIONAL_METHOD   = 'http://purl.org/dc/terms/instructionalMethod';
    const IS_FORMAT_OF           = 'http://purl.org/dc/terms/isFormatOf';
    const IS_PART_OF             = 'http://purl.org/dc/terms/isPartOf';
    const IS_REFERENCED_BY       = 'http://purl.org/dc/terms/isReferencedBy';
    const IS_REPLACED_BY         = 'http://purl.org/dc/terms/isReplacedBy';
    const IS_REQUIRED_BY         = 'http://purl.org/dc/terms/isRequiredBy';
    const ISSUED                 = 'http://purl.org/dc/terms/issued';
    const IS_VERSION_OF          = 'http://purl.org/dc/terms/isVersionOf';
    const LANGUAGE               = 'http://purl.org/dc/terms/language';
    const LICENSE                = 'http://purl.org/dc/terms/license';
    const MEDIATOR               = 'http://purl.org/dc/terms/mediator';
    const MEDIUM                 = 'http://purl.org/dc/terms/medium';
    const MODIFIED               = 'http://purl.org/dc/terms/modified';
    const PROVENANCE             = 'http://purl.org/dc/terms/provenance';
    const PUBLISHER              = 'http://purl.org/dc/terms/publisher';
    const REFERENCES             = 'http://purl.org/dc/terms/references';
    const RELATION               = 'http://purl.org/dc/terms/relation';
    const REPLACES               = 'http://purl.org/dc/terms/replaces';
    const REQUIRES               = 'http://purl.org/dc/terms/requires';
    const RIGHTS                 = 'http://purl.org/dc/terms/rights';
    const RIGHTS_HOLDER          = 'http://purl.org/dc/terms/rightsHolder';
    const SOURCE                 = 'http://purl.org/dc/terms/source';
    const SPATIAL                = 'http://purl.org/dc/terms/spatial';
    const SUBJECT                = 'http://purl.org/dc/terms/subject';
    const TABLE_OF_CONTENTS      = 'http://purl.org/dc/terms/tableOfContents';
    const TEMPORAL               = 'http://purl.org/dc/terms/temporal';
    const TITLE                  = 'http://purl.org/dc/terms/title';
    const TYPE                   = 'http://purl.org/dc/terms/type';
    const VALID                  = 'http://purl.org/dc/terms/valid';

    const literaltypes = [
        'http://purl.org/dc/terms/created'         => 'xsd:dateTime',
        'http://purl.org/dc/terms/date'            => 'xsd:dateTime',
        'http://purl.org/dc/terms/dateAccepted'    => 'xsd:dateTime',
        'http://purl.org/dc/terms/dateApproved'    => 'xsd:dateTime',
        'http://purl.org/dc/terms/dateCopyrighted' => 'xsd:dateTime',
        'http://purl.org/dc/terms/dateSubmitted'   => 'xsd:dateTime',
        'http://purl.org/dc/terms/description'     => 'xsd:string',
        'http://purl.org/dc/terms/license'         => 'xsd:string',
        'http://purl.org/dc/terms/modified'        => 'xsd:dateTime',
        'http://purl.org/dc/terms/title'           => 'xsd:string',
    ];

    /**
     * Returns the first encountered error for created.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="dcterms-validate-created-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the created predicate has a different type than 'http://www.w3.org/2001/XMLSchema#dateTime'"
     *     )
     */
    public function validateCreated($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#dateTime' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'dcterms-validate-created-literal-type',
                    'data' => [
                        'expected' => 'http://www.w3.org/2001/XMLSchema#dateTime',
                        'actual'   => $property->typeIri()->getUri(),
                    ],
                ];
            }
        }
        if (is_null($value)) {
            return null;
        }

        return null;
    }

    /**
     * Returns the first encountered error for date.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="dcterms-validate-date-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the date predicate has a different type than 'http://www.w3.org/2001/XMLSchema#dateTime'"
     *     )
     */
    public function validateDate($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#dateTime' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'dcterms-validate-date-literal-type',
                    'data' => [
                        'expected' => 'http://www.w3.org/2001/XMLSchema#dateTime',
                        'actual'   => $property->typeIri()->getUri(),
                    ],
                ];
            }
        }
        if (is_null($value)) {
            return null;
        }

        return null;
    }

    /**
     * Returns the first encountered error for dateAccepted.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="dcterms-validate-dateaccepted-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the dateaccepted predicate has a different type than 'http://www.w3.org/2001/XMLSchema#dateTime'"
     *     )
     */
    public function validateDateAccepted($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#dateTime' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'dcterms-validate-dateaccepted-literal-type',
                    'data' => [
                        'expected' => 'http://www.w3.org/2001/XMLSchema#dateTime',
                        'actual'   => $property->typeIri()->getUri(),
                    ],
                ];
            }
        }
        if (is_null($value)) {
            return null;
        }

        return null;
    }

    /**
     * Returns the first encountered error for dateApproved.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="dcterms-validate-dateapproved-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the dateapproved predicate has a different type than 'http://www.w3.org/2001/XMLSchema#dateTime'"
     *     )
     */
    public function validateDateApproved($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#dateTime' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'dcterms-validate-dateapproved-literal-type',
                    'data' => [
                        'expected' => 'http://www.w3.org/2001/XMLSchema#dateTime',
                        'actual'   => $property->typeIri()->getUri(),
                    ],
                ];
            }
        }
        if (is_null($value)) {
            return null;
        }

        return null;
    }

    /**
     * Returns the first encountered error for dateCopyrighted.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="dcterms-validate-datecopyrighted-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the datecopyrighted predicate has a different type than 'http://www.w3.org/2001/XMLSchema#dateTime'"
     *     )
     */
    public function validateDateCopyrighted($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#dateTime' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'dcterms-validate-datecopyrighted-literal-type',
                    'data' => [
                        'expected' => 'http://www.w3.org/2001/XMLSchema#dateTime',
                        'actual'   => $property->typeIri()->getUri(),
                    ],
                ];
            }
        }
        if (is_null($value)) {
            return null;
        }

        return null;
    }

    /**
     * Returns the first encountered error for dateSubmitted.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="dcterms-validate-datesubmitted-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the datesubmitted predicate has a different type than 'http://www.w3.org/2001/XMLSchema#dateTime'"
     *     )
     */
    public function validateDateSubmitted($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#dateTime' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'dcterms-validate-datesubmitted-literal-type',
                    'data' => [
                        'expected' => 'http://www.w3.org/2001/XMLSchema#dateTime',
                        'actual'   => $property->typeIri()->getUri(),
                    ],
                ];
            }
        }
        if (is_null($value)) {
            return null;
        }

        return null;
    }

    /**
     * Returns the first encountered error for description.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="dcterms-validate-description-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the description predicate has a different type than 'http://www.w3.org/2001/XMLSchema#string'"
     *     )
     */
    public function validateDescription($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#string' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'dcterms-validate-description-literal-type',
                    'data' => [
                        'expected' => 'http://www.w3.org/2001/XMLSchema#string',
                        'actual'   => $property->typeIri()->getUri(),
                    ],
                ];
            }
        }
        if (is_null($value)) {
            return null;
        }

        return null;
    }

    /**
     * Returns the first encountered error for license.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="dcterms-validate-license-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the license predicate has a different type than 'http://www.w3.org/2001/XMLSchema#string'"
     *     )
     */
    public function validateLicense($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#string' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'dcterms-validate-license-literal-type',
                    'data' => [
                        'expected' => 'http://www.w3.org/2001/XMLSchema#string',
                        'actual'   => $property->typeIri()->getUri(),
                    ],
                ];
            }
        }
        if (is_null($value)) {
            return null;
        }

        return null;
    }

    /**
     * Returns the first encountered error for modified.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="dcterms-validate-modified-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the modified predicate has a different type than 'http://www.w3.org/2001/XMLSchema#dateTime'"
     *     )
     */
    public function validateModified($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#dateTime' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'dcterms-validate-modified-literal-type',
                    'data' => [
                        'expected' => 'http://www.w3.org/2001/XMLSchema#dateTime',
                        'actual'   => $property->typeIri()->getUri(),
                    ],
                ];
            }
        }
        if (is_null($value)) {
            return null;
        }

        return null;
    }

    /**
     * Returns the first encountered error for title.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="dcterms-validate-title-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the title predicate has a different type than 'http://www.w3.org/2001/XMLSchema#string'"
     *     )
     */
    public function validateTitle($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#string' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'dcterms-validate-title-literal-type',
                    'data' => [
                        'expected' => 'http://www.w3.org/2001/XMLSchema#string',
                        'actual'   => $property->typeIri()->getUri(),
                    ],
                ];
            }
        }
        if (is_null($value)) {
            return null;
        }

        return null;
    }
}
