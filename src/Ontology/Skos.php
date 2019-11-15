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

final class Skos
{
    const NAME_SPACE          = 'http://www.w3.org/2004/02/skos/core#';
    const CONCEPT             = 'http://www.w3.org/2004/02/skos/core#Concept';
    const CONCEPT_SCHEME      = 'http://www.w3.org/2004/02/skos/core#ConceptScheme';
    const IN_SCHEME           = 'http://www.w3.org/2004/02/skos/core#inScheme';
    const HAS_TOP_CONCEPT     = 'http://www.w3.org/2004/02/skos/core#hasTopConcept';
    const TOP_CONCEPT_OF      = 'http://www.w3.org/2004/02/skos/core#topConceptOf';
    const ALT_LABEL           = 'http://www.w3.org/2004/02/skos/core#altLabel';
    const HIDDEN_LABEL        = 'http://www.w3.org/2004/02/skos/core#hiddenLabel';
    const PREF_LABEL          = 'http://www.w3.org/2004/02/skos/core#prefLabel';
    const NOTATION            = 'http://www.w3.org/2004/02/skos/core#notation';
    const CHANGE_NOTE         = 'http://www.w3.org/2004/02/skos/core#changeNote';
    const DEFINITION          = 'http://www.w3.org/2004/02/skos/core#definition';
    const EDITORIAL_NOTE      = 'http://www.w3.org/2004/02/skos/core#editorialNote';
    const EXAMPLE             = 'http://www.w3.org/2004/02/skos/core#example';
    const HISTORY_NOTE        = 'http://www.w3.org/2004/02/skos/core#historyNote';
    const NOTE                = 'http://www.w3.org/2004/02/skos/core#note';
    const SCOPE_NOTE          = 'http://www.w3.org/2004/02/skos/core#scopeNote';
    const BROADER             = 'http://www.w3.org/2004/02/skos/core#broader';
    const BROADER_TRANSITIVE  = 'http://www.w3.org/2004/02/skos/core#broaderTransitive';
    const NARROWER            = 'http://www.w3.org/2004/02/skos/core#narrower';
    const NARROWER_TRANSITIVE = 'http://www.w3.org/2004/02/skos/core#narrowerTransitive';
    const RELATED             = 'http://www.w3.org/2004/02/skos/core#related';
    const SEMANTIC_RELATION   = 'http://www.w3.org/2004/02/skos/core#semanticRelation';
    const COLLECTION          = 'http://www.w3.org/2004/02/skos/core#Collection';
    const ORDERED_COLLECTION  = 'http://www.w3.org/2004/02/skos/core#OrderedCollection';
    const MEMBER              = 'http://www.w3.org/2004/02/skos/core#member';
    const MEMBER_LIST         = 'http://www.w3.org/2004/02/skos/core#memberList';
    const BROAD_MATCH         = 'http://www.w3.org/2004/02/skos/core#broadMatch';
    const BROADER_MATCH       = 'http://www.w3.org/2004/02/skos/core#broaderMatch';
    const CLOSE_MATCH         = 'http://www.w3.org/2004/02/skos/core#closeMatch';
    const EXACT_MATCH         = 'http://www.w3.org/2004/02/skos/core#exactMatch';
    const MAPPING_RELATION    = 'http://www.w3.org/2004/02/skos/core#mappingRelation';
    const NARROW_MATCH        = 'http://www.w3.org/2004/02/skos/core#narrowMatch';
    const NARROWER_MATCH      = 'http://www.w3.org/2004/02/skos/core#narrowerMatch';
    const RELATED_MATCH       = 'http://www.w3.org/2004/02/skos/core#relatedMatch';
}
