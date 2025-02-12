<?php

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

class AbstractApiMock extends MockHttpClient
{
    private string $baseUri = 'https://api.mock.internal';

    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);

        parent::__construct($callback, $this->baseUri);
    }

    private function handleRequests(string $method, string $url): MockResponse
    {
        $parsed = parse_url($url);

        if (!isset($parsed['path'])) {
            throw new \UnexpectedValueException("Mock not implemented: $method/$url");
        }

        return match ($parsed['path']) {
            '/generate' => $this->getGenerateMock(),
            '/pull' => $this->pullMock(),
            '/chat/completions' => $this->getGenerateMock(),
            default => throw new \UnexpectedValueException("Mock not implemented: $method/$url"),
        };
    }

    private function pullMock(): MockResponse
    {
        return new MockResponse(
            json_encode([], JSON_THROW_ON_ERROR),
            ['http_code' => Response::HTTP_OK]
        );
    }

    /**
     * "/v1" endpoint.
     */
    private function getGenerateMock(): MockResponse
    {
        $mock = [
            'response' => '{"summary": "This is a valid response from the communicator"}',
            'choices' => [
                ['message' => ['content' => '{"summary": "This is a valid response from the communicator"}']],
            ],
        ];

        return new MockResponse(
            json_encode($mock, JSON_THROW_ON_ERROR),
            ['http_code' => Response::HTTP_OK]
        );
    }
}
