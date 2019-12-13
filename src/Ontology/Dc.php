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

final class Dc
{
    const NAME_SPACE  = 'http://purl.org/dc/elements/1.1/';
    const CREATOR     = 'http://purl.org/dc/elements/1.1/creator';
    const CONTRIBUTOR = 'http://purl.org/dc/elements/1.1/contributor';
    const TITLE       = 'http://purl.org/dc/elements/1.1/title';

    const literaltypes = [
        'http://purl.org/dc/elements/1.1/title' => 'xsd:string',
    ];

    /**
     * Returns the first encountered error for title.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="dc-validate-title-literal-type",
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
                    'code' => 'dc-validate-title-literal-type',
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
