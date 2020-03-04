<?php

declare(strict_types=1);


namespace App\OpenSkos\Filters;


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

}
