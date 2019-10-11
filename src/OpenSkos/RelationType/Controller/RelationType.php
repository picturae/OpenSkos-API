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

        $semanticRelation = $graph->resource('openskos:semanticRelation');
        $semanticRelation->setType('owl:ObjectProperty');
        $semanticRelation->addLiteral('rdfs:domain', 'openskos:Concept');
        $semanticRelation->addLiteral('rdfs:range', 'openskos:Concept');

        $related = $graph->resource('openskos:related');
        $related->setType('owl:SymmetricProperty');
        $related->addResource('rdfs:subPropertyOf', $semanticRelation);

        $broaderTransitive = $graph->resource('openskos:broaderTransitive');
        $broaderTransitive->setType('owl:TransitiveProperty');
        $broaderTransitive->addResource('rdfs:subPropertyOf', $semanticRelation);

        $narrowerTransitive = $graph->resource('openskos:narrowerTransitive');
        $narrowerTransitive->setType('owl:TransitiveProperty');
        $narrowerTransitive->addResource('rdfs:subPropertyOf', $semanticRelation);

        $broader = $graph->resource('openskos:broader');
        $broader->setType('owl:ObjectProperty');
        $broader->addResource('rdfs:subPropertyOf', $broaderTransitive);

        $narrower = $graph->resource('openskos:narrower');
        $narrower->setType('owl:ObjectProperty');
        $narrower->addResource('rdfs:subPropertyOf', $narrowerTransitive);

        return new DirectGraphResponse(
          $graph,
          $apiRequest->getFormat()
        );
    }
}
