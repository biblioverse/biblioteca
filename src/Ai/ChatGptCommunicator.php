<?php

namespace App\Ai;

use App\Ai\Prompt\AbstractBookPrompt;
use Orhanerday\OpenAi\OpenAi;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ChatGptCommunicator implements AiCommunicatorInterface
{
    private array $openAiConfig;

    public function __construct(
        #[Autowire(param: 'OPEN_AI_API_KEY')]
        private readonly ?string $openAiApiKey,
        #[Autowire(param: 'OPEN_AI_MODEL')]
        private readonly ?string $openAiModel,
    ) {
    }

    #[\Override]
    public function isEnabled(): bool
    {
        return $this->openAiApiKey !== null;
    }

    #[\Override]
    public function initialise(string $basePrompt): void
    {
        $this->openAiConfig = [
            'model' => $this->openAiModel,
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

    #[\Override]
    public function interrogate(AbstractBookPrompt $prompt): string|array
    {
        $open_ai = new OpenAi($this->openAiApiKey);

        $chat = $open_ai->chat($this->prepareConfig($prompt->getPrompt()));

        if (!is_string($chat)) {
            throw new \RuntimeException('Failed to communicate with OpenAI');
        }
        $d = json_decode($chat, false);

        // @phpstan-ignore-next-line
        return $prompt->convertResult($d->choices[0]->message->content);
    }
}
