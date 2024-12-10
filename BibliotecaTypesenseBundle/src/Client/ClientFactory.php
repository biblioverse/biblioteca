<?php

namespace Biblioteca\TypesenseBundle\Client;

use Typesense\Client;
use Typesense\Exceptions\ConfigError;

class ClientFactory
{
    public function __construct(
        private readonly string $uri,
        #[\SensitiveParameter] private readonly string $apiKey,
        private readonly int $connectionTimeoutSeconds = 5,
    ) {
    }

    /**
     * @throws ConfigError
     */
    public function __invoke(): ClientInterface
    {
        return new ClientAdapter(new Client($this->getConfiguration()));
    }

    private function getConfiguration(): array
    {
        $urlParsed = parse_url($this->uri);
        if ($urlParsed === false) {
            throw new \InvalidArgumentException('Invalid URI');
        }

        return [
            'nodes' => [
                [
                    'host' => $urlParsed['host'],
                    'port' => $urlParsed['port'],
                    'protocol' => $urlParsed['scheme'],
                ],
            ],
            'api_key' => $this->apiKey,
            'connection_timeout_seconds' => $this->connectionTimeoutSeconds,
        ];
    }
}
