<?php

namespace App\Ai\Communicator;

use App\Ai\Message;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiCommunicator extends AbstractCommunicator implements AiChatInterface
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    private function sendRequest(string $url, array $data = [], string $method = 'GET'): string
    {
        $this->client = $this->client->withOptions(['headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json']]);

        $response = $this->client->request(
            $method,
            $url,
            [
                'json' => $data,
                'timeout' => 600,
            ]
        );

        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $result = '';

        if (!is_array($content) || !array_key_exists('candidates', $content)) {
            throw new \RuntimeException('Failed to communicate with '.$this->aiModel);
        }
        if (!is_array($content['candidates'])) {
            throw new \RuntimeException('Failed to communicate with '.$this->aiModel);
        }
        foreach ($content['candidates'] as $item) {
            if (!is_array($item) || !array_key_exists('content', $item)) {
                throw new \RuntimeException('Failed to communicate with '.$this->aiModel);
            }
            if (!is_array($item['content']) || !array_key_exists('parts', $item['content'])) {
                throw new \RuntimeException('Failed to communicate with '.$this->aiModel);
            }
            if (!is_array($item['content']['parts'])) {
                throw new \RuntimeException('Failed to communicate with '.$this->aiModel);
            }
            foreach ($item['content']['parts'] as $part) {
                if (!is_array($part) || !array_key_exists('text', $part)) {
                    throw new \RuntimeException('Failed to communicate with '.$this->aiModel);
                }
                if (!is_string($part['text'])) {
                    throw new \RuntimeException('Failed to communicate with '.$this->aiModel);
                }
                $result .= $part['text'];
            }
        }
        $result = trim($result);
        if ($result === '') {
            throw new \RuntimeException('Failed to communicate with '.$this->aiModel);
        }

        return $result;
    }

    private function getApiUrl(string $path): string
    {
        if (!str_ends_with((string) $this->aiModel->getUrl(), '/')) {
            $this->aiModel->setUrl($this->aiModel->getUrl().'/');
        }

        return $this->aiModel->getUrl().$this->aiModel->getModel().":$path?key=".$this->aiModel->getToken();
    }

    #[\Override]
    public function interrogate(string $prompt): string
    {
        $params = [
            'contents' => [
                'parts' => [
                    'text' => $prompt,
                ],
            ],
            'tools' => [
                'google_search' => new \stdClass(),
            ],
        ];

        return $this->sendRequest($this->getApiUrl('generateContent'), $params, 'POST');
    }

    public function chat(array $messages): string
    {
        $message = array_pop($messages);

        if (!$message instanceof Message) {
            throw new \RuntimeException('Invalid message type');
        }

        return $this->interrogate($message->getText());
    }
}
