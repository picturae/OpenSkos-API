<?php

declare(strict_types=1);


namespace App\OpenSkos\Filters;


use App\Ontology\OpenSkos;
use App\OpenSkos\InternalResourceId;
use App\Rdf\Iri;
use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;

final class FilterProcessorHelper
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * FilterProcessorHelper constructor.
     * @param Connection $connection
     * @param ContainerInterface $container
     */
    public function __construct(Connection $connection, ContainerInterface $container)
    {
        $this->connection = $connection;
        $this->container = $container;
    }


    /**
     * @param Iri $institution
     * @return string|null
     */
    public function resolveInstitutionsToCode(Iri $institution): ?string
    {
        $ret_val = null;

        $institution_repository = $this->container->get('App\OpenSkos\Institution\InstitutionRepository');

        $institution = $institution_repository->get($institution);

        if ($institution) {
            $code_literal = $institution->getProperty(OpenSkos::CODE);
            if (isset($code_literal[0])) {
                $ret_val = $code_literal[0]->value();
            }
        }

        return $ret_val;
    }

    /**
     * @param string $code
     * @return string|null
     * @throws \App\Exception\ApiException
     */
    public function resolveSetToUriLiteral(string $code): ?string
    {
        $ret_val = 'xxxxxxxxxxxxxxxxxxxxxxxx';

        $set_repository = $this->container->get('App\OpenSkos\Set\SetRepository');
        $set            = $set_repository->findBy(new Iri(Openskos::CODE), new InternalResourceId($code));
        if (isset($set[0])) {
            $uri_literal = $set[0]->getProperty(OpenSkos::CONCEPT_BASE_URI);
            $ret_val     = $uri_literal[0]->value();
        }

        return $ret_val;
    }

}
