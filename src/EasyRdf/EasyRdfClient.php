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

    /**
     * @param Triple[] $triples
     */
    public function insertTriples(array $triples): \EasyRdf_Http_Response
    {
        $tripleString = implode("\n", array_map(function (Triple $triple) {
            return $triple.' .';
        }, $triples))."\n";

        return $this->easyRdfClient->insert($tripleString);
    }

    public function delete(SparqlQuery $query): bool
    {
        $result = $this->easyRdfClient->update($query->rawSparql());
        $status = $result->getStatus();

        if ($status < 200) {
            return false;
        }

        if ($status >= 300) {
            return false;
        }

        return true;
    }
}
