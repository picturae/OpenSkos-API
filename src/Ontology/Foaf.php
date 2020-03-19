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

final class Foaf
{
    const NAME_SPACE = 'http://xmlns.com/foaf/0.1/';
    const PERSON     = 'http://xmlns.com/foaf/0.1/Person';
    const NAME       = 'http://xmlns.com/foaf/0.1/name';
    const MBOX       = 'http://xmlns.com/foaf/0.1/mbox';

    const literaltypes = [
        'http://xmlns.com/foaf/0.1/name' => 'xsd:string',
        'http://xmlns.com/foaf/0.1/mbox' => 'xsd:string',
    ];

    /**
     * Returns the first encountered error for name.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="foaf-validate-name-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the name predicate has a different type than 'http://www.w3.org/2001/XMLSchema#string'"
     *     )
     */
    public function validateName($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#string' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'foaf-validate-name-literal-type',
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
     * Returns the first encountered error for mbox.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="foaf-validate-mbox-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the mbox predicate has a different type than 'http://www.w3.org/2001/XMLSchema#string'"
     *     )
     * @Error(code="foaf-validate-mbox-regex",
     *        status=422,
     *        fields={"regex","value"},
     *        description="The object for the mbox predicate did not match the configured regex"
     *     )
     */
    public function validateMbox($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#string' !== $property->typeIri()->getUri()) {
                return [
                    'code' => 'foaf-validate-mbox-literal-type',
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

        $regex = '^/[A-Z0-9._%+-]++@[A-Z0-9.-]++\\.[A-Z]{2,}+$/i';
        if (!preg_match($regex, $value)) {
            return [
                'code' => 'foaf-validate-mbox-regex',
                'data' => [
                    'regex' => $regex,
                    'value' => $value,
                ],
            ];
        }

        return null;
    }
}
