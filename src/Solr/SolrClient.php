<?php

declare(strict_types=1);

namespace App\Solr;

use Solarium\Client;

final class SolrClient
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(
        Client $client
    ) {
        $this->client = $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
