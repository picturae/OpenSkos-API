<?php

declare(strict_types=1);

namespace App\Institution\Sparql;

use App\Institution\Institution;
use App\Institution\InstitutionRepository;
use App\Ontology\Org;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\OpenSkosIriFactory;
use App\Rdf\Client;
use App\Rdf\Iri;
use App\Rdf\SparqlQueryBuilder;

final class SparqlInstitutionRepository implements InstitutionRepository
{
    /**
     * @var Client
     */
    private $rdfClient;
    /**
     * @var OpenSkosIriFactory
     */
    private $iriFactory;

    public function __construct(
        Client $rdfClient,
        OpenSkosIriFactory $iriFactory
    ) {
        $this->rdfClient = $rdfClient;
        $this->iriFactory = $iriFactory;
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return Institution[]
     */
    public function all(int $offset = 0, int $limit = 100): array
    {
        $sparql = SparqlQueryBuilder::describeAllOfType(
            new Iri(Org::FORMALORG),
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
        foreach ($groups as $iriString => $group) {
            $res[] = Institution::fromTriples(new Iri($iriString), $group);
        }

        return $res;
    }

    public function findByIri(Iri $iri): ?Institution
    {
        $sparql = SparqlQueryBuilder::describeResource($iri);
        $triples = $this->rdfClient->describe($sparql);
        if (0 === count($triples)) {
            return null;
        }

        return Institution::fromTriples($iri, $triples);
    }

    /**
     * @param InternalResourceId $id
     *
     * @return Institution|null
     */
    public function find(InternalResourceId $id): ?Institution
    {
        $iri = $this->iriFactory->fromInternalResourceId($id);

        return $this->findByIri($iri);
    }
}
