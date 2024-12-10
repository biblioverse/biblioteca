<?php

namespace Biblioteca\TypesenseBundle\Client;

use Typesense\Aliases;
use Typesense\Analytics;
use Typesense\Collections;
use Typesense\Debug;
use Typesense\Health;
use Typesense\Keys;
use Typesense\Metrics;
use Typesense\MultiSearch;
use Typesense\Operations;
use Typesense\Presets;

interface ClientInterface
{
    public function getCollections(): Collections;

    public function getAliases(): Aliases;

    public function getKeys(): Keys;

    public function getDebug(): Debug;

    public function getMetrics(): Metrics;

    public function getHealth(): Health;

    public function getOperations(): Operations;

    public function getMultiSearch(): MultiSearch;

    public function getPresets(): Presets;

    public function getAnalytics(): Analytics;
}
