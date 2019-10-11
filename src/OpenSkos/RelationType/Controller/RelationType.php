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

        // Semantic Relation
        $semanticRelation = $graph->resource('openskos:semanticRelation');
        $semanticRelation->setType('rdf:Property');
        $semanticRelation->addResource('rdf:type', 'owl:ObjectProperty');
        $semanticRelation->addLiteral('rdfs:domain', 'openskos:Concept');
        $semanticRelation->addLiteral('rdfs:range', 'openskos:Concept');

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

        //////////////////////////////////////////////////////////

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

        return new DirectGraphResponse(
          $graph,
          $apiRequest->getFormat()
        );
    }
}
