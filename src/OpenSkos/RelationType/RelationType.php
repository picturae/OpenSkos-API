<?php

declare(strict_types=1);

namespace App\OpenSkos\RelationType;

use App\Ontology\Context;
use App\Ontology\Dc;
use App\Ontology\OpenSkos;
use App\Ontology\Owl;
use App\Ontology\Rdf;
use App\Ontology\Rdfs;
use App\Ontology\Skos;

final class RelationType
{
    public static function vocabularyFields(): array
    {
        return array_filter(
            array_keys(static::vocabulary()->resources()),
            function ($predicate) {
                if (!Context::decodeUri($predicate)) {
                    return false;
                }

                return !in_array($predicate, [
                    Owl::OBJECT_PROPERTY,
                    Owl::ONTOLOGY,
                    Owl::SYMMETRIC_PROPERTY,
                    Owl::TRANSITIVE_PROPERTY,
                    Rdf::PROPERTY,
                    Skos::CONCEPT,
                    Skos::CONCEPT_SCHEME,
                    Skos::MAPPING_RELATION,
                    Skos::SEMANTIC_RELATION,
                ], true);

                return true;
            }
        );
    }

    public static function semanticFields(): array
    {
        return array_filter(static::vocabularyFields(), function ($predicate) {
            return !in_array($predicate, [
                Skos::HAS_TOP_CONCEPT,
                Skos::IN_SCHEME,
                Skos::TOP_CONCEPT_OF,
                OpenSkos::IN_COLLECTION,
                OpenSkos::IN_SET,
                OpenSkos::IS_REPLACED_BY,
                OpenSkos::REPLACES,
            ], true);
        });
    }

    public static function vocabulary(): \EasyRdf_Graph
    {
        \EasyRdf_Namespace::set('dc', Dc::NAME_SPACE);
        \EasyRdf_Namespace::set('skos', Skos::NAME_SPACE);
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);
        \EasyRdf_Namespace::set('owl', Owl::NAME_SPACE);
        \EasyRdf_Namespace::set('rdf', Rdf::NAME_SPACE);
        \EasyRdf_Namespace::set('rdfs', Rdfs::NAME_SPACE);

        // Define graph structure
        $graph = new \EasyRdf_Graph();

        // Intro
        $openskos = $graph->resource('skos');
        $openskos->setType('owl:Ontology');
        $openskos->addLiteral('dc:title', 'Skos RelationType vocabulary');

        ///////////////////////////////////
        // Semantic Relation             //
        //                               //
        // Copy of SKOS:semanticRelation //
        ///////////////////////////////////

        $semanticRelation = $graph->resource('skos:semanticRelation');
        $semanticRelation->setType('rdf:Property');
        $semanticRelation->addResource('rdf:type', 'owl:ObjectProperty');
        $semanticRelation->addResource('rdfs:domain', 'skos:Concept');
        $semanticRelation->addResource('rdfs:range', 'skos:Concept');

        $related = $graph->resource('skos:related');
        $related->setType('rdf:Property');
        $related->addResource('rdf:type', 'owl:ObjectProperty');
        $related->addResource('rdf:type', 'owl:SymmetricProperty');
        $related->addResource('rdfs:subPropertyOf', $semanticRelation);

        $broaderTransitive = $graph->resource('skos:broaderTransitive');
        $broaderTransitive->setType('rdf:Property');
        $broaderTransitive->addResource('rdf:type', 'owl:ObjectProperty');
        $broaderTransitive->addResource('rdf:type', 'owl:TransitiveProperty');
        $broaderTransitive->addResource('rdfs:subPropertyOf', $semanticRelation);

        $narrowerTransitive = $graph->resource('skos:narrowerTransitive');
        $narrowerTransitive->setType('rdf:Property');
        $narrowerTransitive->addResource('rdf:type', 'owl:ObjectProperty');
        $narrowerTransitive->addResource('rdf:type', 'owl:TransitiveProperty');
        $narrowerTransitive->addResource('rdfs:subPropertyOf', $semanticRelation);

        $broader = $graph->resource('skos:broader');
        $broader->setType('rdf:Property');
        $broader->addResource('rdf:type', 'owl:ObjectProperty');
        $broader->addResource('rdfs:subPropertyOf', $broaderTransitive);

        $narrower = $graph->resource('skos:narrower');
        $narrower->setType('rdf:Property');
        $narrower->addResource('rdf:type', 'owl:ObjectProperty');
        $narrower->addResource('rdfs:subPropertyOf', $narrowerTransitive);

        //////////////////////////////////
        // Mapping Relation             //
        //                              //
        // Copy of SKOS:mappingRelation //
        //////////////////////////////////

        // Mapping relation
        $mappingRelation = $graph->resource('skos:mappingRelation');
        $mappingRelation->setType('rdf:Property');
        $mappingRelation->addResource('rdf:type', 'owl:ObjectProperty');
        $mappingRelation->addResource('rdfs:subPropertyOf', $semanticRelation);

        $closeMatch = $graph->resource('skos:closeMatch');
        $closeMatch->setType('rdf:Property');
        $closeMatch->addResource('rdf:type', 'owl:ObjectProperty');
        $closeMatch->addResource('rdf:type', 'owl:SymmetricProperty');
        $closeMatch->addResource('rdfs:subPropertyOf', $mappingRelation);

        $exactMatch = $graph->resource('skos:exactMatch');
        $exactMatch->setType('rdf:Property');
        $exactMatch->addResource('rdf:type', 'owl:ObjectProperty');
        $exactMatch->addResource('rdf:type', 'owl:SymmetricProperty');
        $exactMatch->addResource('rdf:type', 'owl:TransitiveProperty');
        $exactMatch->addResource('rdfs:subPropertyOf', $closeMatch);

        $broadMatch = $graph->resource('skos:broadMatch');
        $broadMatch->setType('rdf:Property');
        $broadMatch->addResource('rdf:type', 'owl:ObjectProperty');
        $broadMatch->addResource('rdfs:subPropertyOf', $mappingRelation);
        $broadMatch->addResource('rdfs:subPropertyOf', $broader);

        $narrowMatch = $graph->resource('skos:narrowMatch');
        $narrowMatch->setType('rdf:Property');
        $narrowMatch->addResource('rdf:type', 'owl:ObjectProperty');
        $narrowMatch->addResource('rdfs:subPropertyOf', $mappingRelation);
        $narrowMatch->addResource('rdfs:subPropertyOf', $narrower);

        $relatedMatch = $graph->resource('skos:relatedMatch');
        $relatedMatch->setType('rdf:Property');
        $relatedMatch->addResource('rdf:type', 'owl:ObjectProperty');
        $relatedMatch->addResource('rdf:type', 'owl:SymmetricProperty');
        $relatedMatch->addResource('rdfs:subPropertyOf', $mappingRelation);
        $relatedMatch->addResource('rdfs:subPropertyOf', $related);

        ///////////////////////////
        // inScheme              //
        //                       //
        // Copy of SKOS:inScheme //
        ///////////////////////////

        $inScheme = $graph->resource('skos:inScheme');
        $inScheme->setType('rdf:Property');
        $inScheme->addResource('rdf:type', 'owl:ObjectProperty');
        $inScheme->addResource('rdfs:range', 'skos:ConceptScheme');

        // TODO: inverseOf topConceptOf
        $hasTopConcept = $graph->resource('skos:hasTopConcept');
        $hasTopConcept->setType('rdf:Property');
        $hasTopConcept->addResource('rdf:type', 'owl:ObjectProperty');
        $hasTopConcept->addResource('rdfs:range', 'skos:Concept');
        $hasTopConcept->addResource('rdfs:domain', 'skos:ConceptScheme');

        // TODO: inverseOf hasTopConcept
        $topConceptOf = $graph->resource('skos:topConceptOf');
        $topConceptOf->setType('rdf:Property');
        $topConceptOf->addResource('rdf:type', 'owl:ObjectProperty');
        $topConceptOf->addResource('rdfs:subPropertyOf', $inScheme);

        ////////////////////////
        // CUSTOM DEFINITIONS //
        ////////////////////////

        // TODO: supPropertyOf exactMatch?
        // TODO: inverseOf replaces?
        $isReplacedBy = $graph->resource('openskos:isReplacedBy');
        $isReplacedBy->setType('rdf:Property');
        $isReplacedBy->addResource('rdf:type', 'owl:ObjectProperty');

        // TODO: supPropertyOf exactMatch?
        // TODO: inverseOf isReplacedBy?
        $replaces = $graph->resource('openskos:replaces');
        $replaces->setType('rdf:Property');
        $replaces->addResource('rdf:type', 'owl:ObjectProperty');

        $inCollection = $graph->resource('openskos:inCollection');
        $inCollection->setType('rdf:Property');
        $inCollection->addResource('rdf:type', 'owl:ObjectProperty');

        $inSet = $graph->resource('openskos:inSet');
        $inSet->setType('rdf:Property');
        $inSet->addResource('rdf:type', 'owl:ObjectProperty');

        // ON HOLD:
        //   - member

        // TO BE DEFINED:
        //   - labelRelation
        //   - {user relation types}

        return $graph;
    }
}
