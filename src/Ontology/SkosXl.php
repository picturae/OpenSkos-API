<?php

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

final class SkosXl
{
    const NAME_SPACE     = 'http://www.w3.org/2008/05/skos-xl#';
    const LABEL          = 'http://www.w3.org/2008/05/skos-xl#Label';
    const ALT_LABEL      = 'http://www.w3.org/2008/05/skos-xl#altLabel';
    const HIDDEN_LABEL   = 'http://www.w3.org/2008/05/skos-xl#hiddenLabel';
    const LABEL_RELATION = 'http://www.w3.org/2008/05/skos-xl#labelRelation';
    const LITERAL_FORM   = 'http://www.w3.org/2008/05/skos-xl#literalForm';
    const PREF_LABEL     = 'http://www.w3.org/2008/05/skos-xl#prefLabel';

    const literaltypes = [
        'http://www.w3.org/2008/05/skos-xl#altLabel'    => 'xsd:string',
        'http://www.w3.org/2008/05/skos-xl#hiddenLabel' => 'xsd:string',
        'http://www.w3.org/2008/05/skos-xl#literalForm' => 'xsd:string',
        'http://www.w3.org/2008/05/skos-xl#prefLabel'   => 'xsd:string',
    ];

    /**
     * Returns the first encountered error for altLabel.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="skosxl-validate-altlabel-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the altlabel predicate has a different type than 'http://www.w3.org/2001/XMLSchema#string'"
     *     )
     */
    public function validateAltLabel($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#string' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'skosxl-validate-altlabel-literal-type',
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
     * Returns the first encountered error for hiddenLabel.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="skosxl-validate-hiddenlabel-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the hiddenlabel predicate has a different type than 'http://www.w3.org/2001/XMLSchema#string'"
     *     )
     */
    public function validateHiddenLabel($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#string' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'skosxl-validate-hiddenlabel-literal-type',
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
     * Returns the first encountered error for literalForm.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="skosxl-validate-literalform-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the literalform predicate has a different type than 'http://www.w3.org/2001/XMLSchema#string'"
     *     )
     */
    public function validateLiteralForm($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#string' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'skosxl-validate-literalform-literal-type',
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
     * Returns the first encountered error for prefLabel.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="skosxl-validate-preflabel-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the preflabel predicate has a different type than 'http://www.w3.org/2001/XMLSchema#string'"
     *     )
     */
    public function validatePrefLabel($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#string' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'skosxl-validate-preflabel-literal-type',
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
