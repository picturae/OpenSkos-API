<?php

declare(strict_types=1);

namespace App\Set\Sparql;

use App\Rdf\Sparql\SparqlQuery;
use App\Set\Set;
use App\Set\SetRepository;
use App\Ontology\OpenSkos;
use App\Rdf\Sparql\Client;
use App\Rdf\Iri;

final class SparqlSetRepository implements SetRepository
{
    /**
     * @var \App\Rdf\Sparql\Client
     */
    private $rdfClient;

    public function __construct(
        Client $rdfClient
    ) {
        $this->rdfClient = $rdfClient;
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return Set[]
     */
    public function all(int $offset = 0, int $limit = 100): array
    {
        $sparql = SparqlQuery::describeAllOfType(
            new Iri(Openskos::SET),
            $offset,
            $limit
        );
        $triples = $this->rdfClient->describe($sparql);

        //TODO: Move to separate helper class?
        $groups = [];
        foreach ($triples as $triple) {
            $groups[$triple->getSubject()->getUri()][] = $triple;
        }

        $res = [];
//        foreach ($groups as $iriString => $group) {
//            $res[] = Set::fromTriples(new Iri($iriString), $group);
//        }

        return $res;
    }

    public function find(Iri $iri): ?Set
    {
        throw new \RuntimeException('Not implemented');
    }
}
