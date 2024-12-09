<?php

namespace Biblioteca\TypesenseBundle\Client;

use Typesense\Aliases;
use Typesense\Analytics;
use Typesense\Client;
use Typesense\Collections;
use Typesense\Debug;
use Typesense\Health;
use Typesense\Keys;
use Typesense\Metrics;
use Typesense\MultiSearch;
use Typesense\Operations;
use Typesense\Presets;

class ClientAdapter implements ClientInterface
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function __call($name, $arguments)
    {
        return $this->client->$name(...$arguments);
    }

    public function getCollections(): Collections
    {
        return $this->client->getCollections();
    }

    public function getAliases(): Aliases
    {
        return $this->client->getAliases();
    }

    public function getKeys(): Keys
    {
        return $this->client->getKeys();
    }

    public function getDebug(): Debug
    {
        return $this->client->getDebug();
    }

    public function getMetrics(): Metrics
    {
        return $this->client->getMetrics();
    }

    public function getHealth(): Health
    {
        return $this->client->getHealth();
    }

    public function getOperations(): Operations
    {
        return $this->client->getOperations();
    }

    public function getMultiSearch(): MultiSearch
    {
        return $this->client->getMultiSearch();
    }

    public function getPresets(): Presets
    {
        return $this->client->getPresets();
    }

    public function getAnalytics(): Analytics
    {
        return $this->client->getAnalytics();
    }
}
