<?php

namespace App\EasyRdf;

use App\Rdf\Exception\InvalidSparqlQuery;
use App\Rdf\Sparql\Client;
use App\Rdf\Sparql\SparqlQuery;
use App\Rdf\Triple;
use EasyRdf_Graph;
use EasyRdf_Sparql_Client;

class EasyRdfClient implements Client
{
    /**
     * @var EasyRdf_Sparql_Client
     */
    private $easyRdfClient;

    public function __construct(
        EasyRdf_Sparql_Client $easyRdfClient
    ) {
        $this->easyRdfClient = $easyRdfClient;
    }

    /**
     * @return Triple[]
     */
    public function describe(SparqlQuery $query): array
    {
        $graph = $this->easyRdfClient->query($query->rawSparql());

        if (!$graph instanceof EasyRdf_Graph) {
            // TODO: Add to SparqlQuery object isDescribe() method for such checks.
            throw InvalidSparqlQuery::causedBy($query, 'Is not a DESCRIBE query');
        }

        return TripleFactory::triplesFromGraph($graph);
    }

    public function fetch(SparqlQuery $query): object
    {
        $graph = $this->easyRdfClient->query($query->rawSparql());

        return $graph;
    }

    public function insertTriples(string $triples): \EasyRdf_Http_Response
    {
        return $this->easyRdfClient->insert($triples);
    }
}
