<?php

namespace App\Ai;

use App\Suggestion\BookPromptInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaCommunicator implements AiCommunicatorInterface
{
    private string $basePrompt = '';

    public function __construct(
        private readonly HttpClientInterface $client,
    ) {
    }

    public function isEnabled(): bool
    {
        return true;
    }

    private function sendRequest(string $url, array $data = [], string $method = 'GET'): string
    {
        $response = $this->client->request(
            $method,
            $url,
            [
                'json' => $data,
                'timeout' => 600,
            ]
        );

        $content = [];
        foreach ($this->client->stream($response) as $chunk) {
            $json = $chunk->getContent();
            if ($json === '') {
                continue;
            }
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if ($data === null || !is_array($data) || !array_key_exists('response', $data)) {
                continue;
            }

            $content[] = $data['response'];
        }

        return implode('', $content);
    }

    private function getOllamaUrl(string $path): string
    {
        return "http://10.10.10.55:11434/api/{$path}"; // todo env
    }

    public function initialise(string $basePrompt): void
    {
        $this->sendRequest($this->getOllamaUrl('pull'), [
            'model' => 'llama3.2', // todo env
        ], 'POST');
    }

    public function interrogate(BookPromptInterface $prompt): string|array
    {
        $response = $this->sendRequest($this->getOllamaUrl('generate'), [
            'model' => 'llama3.2', // todo env
            'prompt' => $prompt->getPrompt(),
            'system' => $this->basePrompt,
            'options' => [
                'temperature' => 0,
            ],
        ], 'POST');

        return $prompt->convertResult($response);
    }
}
