<?php

namespace App\Ontology;

final class Skos
{
    const NAME_SPACE = 'http://www.w3.org/2004/02/skos/core#';
    const CONCEPT = 'http://www.w3.org/2004/02/skos/core#Concept';
    const CONCEPTSCHEME = 'http://www.w3.org/2004/02/skos/core#ConceptScheme';
    const INSCHEME = 'http://www.w3.org/2004/02/skos/core#inScheme';
    const HASTOPCONCEPT = 'http://www.w3.org/2004/02/skos/core#hasTopConcept';
    const TOPCONCEPTOF = 'http://www.w3.org/2004/02/skos/core#topConceptOf';
    //LexicalLabels
    const ALTLABEL = 'http://www.w3.org/2004/02/skos/core#altLabel';
    const HIDDENLABEL = 'http://www.w3.org/2004/02/skos/core#hiddenLabel';
    const PREFLABEL = 'http://www.w3.org/2004/02/skos/core#prefLabel';
    //Notations
    const NOTATION = 'http://www.w3.org/2004/02/skos/core#notation';
    //DocumentationProperties
    const CHANGENOTE = 'http://www.w3.org/2004/02/skos/core#changeNote';
    const DEFINITION = 'http://www.w3.org/2004/02/skos/core#definition';
    const EDITORIALNOTE = 'http://www.w3.org/2004/02/skos/core#editorialNote';
    const EXAMPLE = 'http://www.w3.org/2004/02/skos/core#example';
    const HISTORYNOTE = 'http://www.w3.org/2004/02/skos/core#historyNote';
    const NOTE = 'http://www.w3.org/2004/02/skos/core#note';
    const SCOPENOTE = 'http://www.w3.org/2004/02/skos/core#scopeNote';
    //SemanticRelations
    const BROADER = 'http://www.w3.org/2004/02/skos/core#broader';
    const BROADERTRANSITIVE = 'http://www.w3.org/2004/02/skos/core#broaderTransitive';
    const NARROWER = 'http://www.w3.org/2004/02/skos/core#narrower';
    const NARROWERTRANSITIVE = 'http://www.w3.org/2004/02/skos/core#narrowerTransitive';
    const RELATED = 'http://www.w3.org/2004/02/skos/core#related';
    const SEMANTICRELATION = 'http://www.w3.org/2004/02/skos/core#semanticRelation';
    //ConceptCollections
    const SKOSCOLLECTION = 'http://www.w3.org/2004/02/skos/core#Collection';
    const ORDEREDCOLLECTION = 'http://www.w3.org/2004/02/skos/core#OrderedCollection';
    const MEMBER = 'http://www.w3.org/2004/02/skos/core#member';
    const MEMBERLIST = 'http://www.w3.org/2004/02/skos/core#memberList';
    //MappingProperties
    const BROADMATCH = 'http://www.w3.org/2004/02/skos/core#broadMatch';
    const CLOSEMATCH = 'http://www.w3.org/2004/02/skos/core#closeMatch';
    const EXACTMATCH = 'http://www.w3.org/2004/02/skos/core#exactMatch';
    const MAPPINGRELATION = 'http://www.w3.org/2004/02/skos/core#mappingRelation';
    const NARROWMATCH = 'http://www.w3.org/2004/02/skos/core#narrowMatch';
    const RELATEDMATCH = 'http://www.w3.org/2004/02/skos/core#relatedMatch';
    const HTML_REFERENCE = 'http://www.w3.org/2009/08/skos-reference/skos.html';
}
