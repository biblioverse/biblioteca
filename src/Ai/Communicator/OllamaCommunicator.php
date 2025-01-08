<?php

namespace App\Ai\Communicator;

use App\Entity\AiModel;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaCommunicator extends AbstractCommunicator
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    private function sendRequest(string $url, array $data = [], string $method = 'GET'): string
    {
        $this->client = $this->client->withOptions(['headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'Authorization' => 'Bearer '.$this->aiModel->getToken()]]);

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
            try {
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                continue;
            }
            if (!is_array($data) || !array_key_exists('response', $data)) {
                continue;
            }

            $content[] = $data['response'];
        }

        return implode('', $content);
    }

    private function getOllamaUrl(string $path): string
    {
        return "{$this->aiModel->getUrl()}{$path}";
    }

    #[\Override]
    public function initialise(AiModel $model): void
    {
        parent::initialise($model);

        $this->sendRequest($this->getOllamaUrl('pull'), [
            'model' => $this->aiModel->getModel(),
        ], 'POST');
    }

    #[\Override]
    public function interrogate(string $prompt): string
    {
        $params = [
            'model' => $this->aiModel->getModel(),
            'prompt' => $prompt,
            'system' => $this->aiModel->getSystemPrompt(),
            'options' => [
                'temperature' => 0,
            ],
        ];

        return $this->sendRequest($this->getOllamaUrl('generate'), $params, 'POST');
    }
}
