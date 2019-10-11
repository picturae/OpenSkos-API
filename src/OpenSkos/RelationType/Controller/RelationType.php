<?php

declare(strict_types=1);

namespace App\OpenSkos\RelationType\Controller;

use App\Rest\DirectGraphResponse;
use App\OpenSkos\ApiRequest;
use App\Ontology\Dc;
use App\Ontology\OpenSkos;
use App\Ontology\Owl;
use App\Ontology\Rdf;
use App\Ontology\Rdfs;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class RelationType
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Role constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @Route(path="/relationtypes", methods={"GET"})
     *
     * @param ApiRequest $apiRequest
     *
     * @return DirectGraphResponse
     */
    public function relationtypes(
        ApiRequest $apiRequest
    ): DirectGraphResponse {
        \EasyRdf_Namespace::set('dc', Dc::NAME_SPACE);
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);
        \EasyRdf_Namespace::set('owl', Owl::NAME_SPACE);
        \EasyRdf_Namespace::set('rdf', Rdf::NAME_SPACE);
        \EasyRdf_Namespace::set('rdfs', Rdfs::NAME_SPACE);

        // Define graph structure
        $graph = new \EasyRdf_Graph('openskos.org');

        // Intro
        $openskos = $graph->resource('openskos');
        $openskos->setType('owl:Ontology');
        $openskos->addLiteral('dc:title', 'OpenSkos RelationType vocabulary');

        ///////////////////////////////////
        // Semantic Relation             //
        //                               //
        // Copy of SKOS:semanticRelation //
        ///////////////////////////////////

        $semanticRelation = $graph->resource('openskos:semanticRelation');
        $semanticRelation->setType('rdf:Property');
        $semanticRelation->addResource('rdf:type', 'owl:ObjectProperty');
        $semanticRelation->addResource('rdfs:domain', 'openskos:Concept');
        $semanticRelation->addResource('rdfs:range', 'openskos:Concept');

        $related = $graph->resource('openskos:related');
        $related->setType('rdf:Property');
        $related->addResource('rdf:type', 'owl:ObjectProperty');
        $related->addResource('rdf:type', 'owl:SymmetricProperty');
        $related->addResource('rdfs:subPropertyOf', $semanticRelation);

        $broaderTransitive = $graph->resource('openskos:broaderTransitive');
        $broaderTransitive->setType('rdf:Property');
        $broaderTransitive->addResource('rdf:type', 'owl:ObjectProperty');
        $broaderTransitive->addResource('rdf:type', 'owl:TransitiveProperty');
        $broaderTransitive->addResource('rdfs:subPropertyOf', $semanticRelation);

        $narrowerTransitive = $graph->resource('openskos:narrowerTransitive');
        $narrowerTransitive->setType('rdf:Property');
        $narrowerTransitive->addResource('rdf:type', 'owl:ObjectProperty');
        $narrowerTransitive->addResource('rdf:type', 'owl:TransitiveProperty');
        $narrowerTransitive->addResource('rdfs:subPropertyOf', $semanticRelation);

        $broader = $graph->resource('openskos:broader');
        $broader->setType('rdf:Property');
        $broader->addResource('rdf:type', 'owl:ObjectProperty');
        $broader->addResource('rdfs:subPropertyOf', $broaderTransitive);

        $narrower = $graph->resource('openskos:narrower');
        $narrower->setType('rdf:Property');
        $narrower->addResource('rdf:type', 'owl:ObjectProperty');
        $narrower->addResource('rdfs:subPropertyOf', $narrowerTransitive);

        //////////////////////////////////
        // Mapping Relation             //
        //                              //
        // Copy of SKOS:mappingRelation //
        //////////////////////////////////

        // Mapping relation
        $mappingRelation = $graph->resource('openskos:mappingRelation');
        $mappingRelation->setType('rdf:Property');
        $mappingRelation->addResource('rdf:type', 'owl:ObjectProperty');
        $mappingRelation->addResource('rdfs:subPropertyOf', $semanticRelation);

        $closeMatch = $graph->resource('openskos:closeMatch');
        $closeMatch->setType('rdf:Property');
        $closeMatch->addResource('rdf:type', 'owl:ObjectProperty');
        $closeMatch->addResource('rdf:type', 'owl:SymmetricProperty');
        $closeMatch->addResource('rdfs:subPropertyOf', $mappingRelation);

        $exactMatch = $graph->resource('openskos:exactMatch');
        $exactMatch->setType('rdf:Property');
        $exactMatch->addResource('rdf:type', 'owl:ObjectProperty');
        $exactMatch->addResource('rdf:type', 'owl:SymmetricProperty');
        $exactMatch->addResource('rdf:type', 'owl:TransitiveProperty');
        $exactMatch->addResource('rdfs:subPropertyOf', $closeMatch);

        $broadMatch = $graph->resource('openskos:broadMatch');
        $broadMatch->setType('rdf:Property');
        $broadMatch->addResource('rdf:type', 'owl:ObjectProperty');
        $broadMatch->addResource('rdfs:subPropertyOf', $mappingRelation);
        $broadMatch->addResource('rdfs:subPropertyOf', $broader);

        $narrowMatch = $graph->resource('openskos:narrowMatch');
        $narrowMatch->setType('rdf:Property');
        $narrowMatch->addResource('rdf:type', 'owl:ObjectProperty');
        $narrowMatch->addResource('rdfs:subPropertyOf', $mappingRelation);
        $narrowMatch->addResource('rdfs:subPropertyOf', $narrower);

        $relatedMatch = $graph->resource('openskos:relatedMatch');
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

        $inScheme = $graph->resource('openskos:inScheme');
        $inScheme->setType('rdf:Property');
        $inScheme->addResource('rdf:type', 'owl:ObjectProperty');
        $inScheme->addResource('rdfs:range', 'openskos:ConceptScheme');

        // TODO: inverseOf topConceptOf
        $hasTopConcept = $graph->resource('openskos:hasTopConcept');
        $hasTopConcept->setType('rdf:Property');
        $hasTopConcept->addResource('rdf:type', 'owl:ObjectProperty');
        $hasTopConcept->addResource('rdfs:range', 'openskos:Concept');
        $hasTopConcept->addResource('rdfs:domain', 'openskos:ConceptScheme');

        // TODO: inverseOf hasTopConcept
        $topConceptOf = $graph->resource('openskos:topConceptOf');
        $topConceptOf->setType('rdf:Property');
        $topConceptOf->addResource('rdf:type', 'owl:ObjectProperty');
        $topConceptOf->addResource('rdfs:subPropertyOf', $inScheme);

        ////////////
        // labels //
        ////////////

        $prefLabel = $graph->resource('openskos:prefLabel');
        $prefLabel->setType('rdf:Property');
        $prefLabel->addResource('rdf:type', 'owl:AnnotationProperty');
        $prefLabel->addResource('rdf:supPropertyOf', 'rdfs:label');

        $altLabel = $graph->resource('openskos:altLabel');
        $altLabel->setType('rdf:Property');
        $altLabel->addResource('rdf:type', 'owl:AnnotationProperty');
        $altLabel->addResource('rdf:supPropertyOf', 'rdfs:label');

        $hiddenLabel = $graph->resource('openskos:hiddenLabel');
        $hiddenLabel->setType('rdf:Property');
        $hiddenLabel->addResource('rdf:type', 'owl:AnnotationProperty');
        $hiddenLabel->addResource('rdf:supPropertyOf', 'rdfs:label');

        // ON HOLD:
        //   - isPrefLabelOf
        //   - isAltLabelOf
        //   - isHiddenLabelOf

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

        // TODO: subPropertyOf exactMatch?
        $tenant = $graph->resource('openskos:tenant');
        $tenant->setType('rdf:Property');
        $tenant->addResource('rdf:type', 'owl:ObjectProperty');

        // ON HOLD:
        //   - member

        // TO BE DEFINED:
        //   - labelRelation
        //   - {user relation types}

        return new DirectGraphResponse(
          $graph,
          $apiRequest->getFormat()
        );
    }
}
