<?php

namespace App\Ai;

use Orhanerday\OpenAi\OpenAi;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ChatGptCommunicator implements AiCommunicatorInterface
{
    private array $openAiConfig;

    public function __construct(
        #[Autowire(param: 'OPEN_AI_API_KEY')]
        private readonly ?string $openAiApiKey,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->openAiApiKey !== null;
    }

    public function initialise(string $basePrompt): void
    {
        $this->openAiConfig = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $basePrompt,
                ],
            ],
            'temperature' => 0,
            'max_tokens' => 4000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ];
    }

    private function prepareConfig(string $message): array
    {
        $config = $this->openAiConfig;
        $config['messages'][] = [
            'role' => 'user',
            'content' => $message,
        ];

        return $config;
    }

    public function sendMessageForString(string $message): string
    {
        $open_ai = new OpenAi($this->openAiApiKey);

        $chat = $open_ai->chat($this->prepareConfig($message));

        if (!is_string($chat)) {
            throw new \RuntimeException('Failed to communicate with OpenAI');
        }
        $d = json_decode($chat);

        // @phpstan-ignore-next-line
        return $d->choices[0]->message->content;
    }

    public function sendMessageForArray(string $message): array
    {
        $items = explode("\n", $this->sendMessageForString($message));
        array_walk($items, function (&$item) {
            $item = trim($item, '- ');
        });

        return $items;
    }
}
