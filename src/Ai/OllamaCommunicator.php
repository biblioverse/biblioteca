<?php

namespace App\Ai;

use App\Suggestion\BookPromptInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaCommunicator implements AiCommunicatorInterface
{
    private string $basePrompt = '';

    public function __construct(
        private readonly HttpClientInterface $client,
        #[Autowire(param: 'OLLAMA_URL')]
        private readonly ?string $url,
        #[Autowire(param: 'OLLAMA_MODEL')]
        private readonly ?string $model,
    ) {
    }

    #[\Override]
    public function isEnabled(): bool
    {
        return $this->url !== null && $this->model !== null;
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
        return "{$this->url}{$path}";
    }

    #[\Override]
    public function initialise(string $basePrompt): void
    {
        $this->sendRequest($this->getOllamaUrl('pull'), [
            'model' => $this->model,
        ], 'POST');
    }

    #[\Override]
    public function interrogate(BookPromptInterface $prompt): string|array
    {
        $response = $this->sendRequest($this->getOllamaUrl('generate'), [
            'model' => $this->model,
            'prompt' => $prompt->getPrompt(),
            'system' => $this->basePrompt,
            'options' => [
                'temperature' => 0,
            ],
        ], 'POST');

        return $prompt->convertResult($response);
    }
}
