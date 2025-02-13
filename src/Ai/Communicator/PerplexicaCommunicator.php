<?php

namespace App\Ai\Communicator;

use App\Ai\Message;
use App\Entity\AiModel;
use App\Enum\AiMessageRole;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PerplexicaCommunicator extends AbstractCommunicator implements AiChatInterface
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
            if (!is_array($data) || (!array_key_exists('message', $data))) {
                continue;
            }

            $content[] = $data['message'];
        }

        return implode('', $content);
    }

    private function getPerplexicaUrl(string $path): string
    {
        if (!str_ends_with((string) $this->aiModel->getUrl(), '/')) {
            $this->aiModel->setUrl($this->aiModel->getUrl().'/');
        }

        return "{$this->aiModel->getUrl()}{$path}";
    }

    #[\Override]
    public function initialise(AiModel $model): void
    {
        parent::initialise($model);
    }

    #[\Override]
    public function interrogate(string $prompt): string
    {
        $messages = [
            new Message($prompt, AiMessageRole::User),
        ];

        return $this->chat($messages);
    }

    public function chat(array $messages): string
    {
        // https://github.com/ItzCrazyKns/Perplexica/blob/master/docs/API/SEARCH.md

        $processedMessages = [];

        $lastMessage = array_pop($messages);

        if ($lastMessage === null) {
            return '';
        }

        foreach ($messages as $message) {
            $processedMessages[] = $message->toPerplexica();
        }

        $model = $this->aiModel->getModel();
        $modelInfo = [
            'chatModel' => [
                'model' => $model,
                'provider' => 'ollama',
            ],
        ];
        if (str_contains((string) $model, '/')) {
            $exp = explode('/', (string) $model);
            $provider = $exp[0];
            $model = $exp[1];
            $modelInfo = [
                'chatModel' => [
                    'model' => $model,
                    'provider' => $provider,
                ],
            ];
        }

        if ($model === '') {
            $modelInfo = [];
        }

        $params = [
            ...$modelInfo,
            'focusMode' => 'webSearch',
            'optimizationMode' => 'speed',
            'query' => $lastMessage->getText(),
            'messages' => $processedMessages,
        ];

        return $this->sendRequest($this->getPerplexicaUrl('search'), $params, 'POST');
    }
}
