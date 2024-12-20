<?php

namespace App\Twig\Components;

use App\Entity\Book;
use App\Entity\User;
use App\Suggestion\SummaryPrompt;
use App\Suggestion\TagPrompt;
use Orhanerday\OpenAi\OpenAi;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent]
final class ChatGPTSuggestion
{
    use DefaultActionTrait;

    #[LiveProp(writable: ['title', 'serie', 'serieIndex', 'publisher', 'verified', 'summary', 'authors', 'tags', 'ageCategory'])]
    public Book $book;

    #[LiveProp()]
    public string $field;

    public User $user;

    #[LiveProp(writable: true)]
    public string $prompt = '';

    /** @var array<string> */
    public array $result = [];
    public array $suggestions = [];

    public const array EMPTY_SUGGESTIONS = [
        'image' => [],
        'title' => [],
        'authors' => [],
        'publisher' => [],
        'tags' => [],
        'summary' => [],
    ];

    public function __construct(
        private Security $security,
        private TagPrompt $tagPrompt,
        private SummaryPrompt $summaryPrompt,
    ) {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('User must be logged in');
        }
        $this->user = $user;
    }

    #[PostMount]
    public function postMount(): void
    {
        $this->prompt = match ($this->field) {
            'summary' => $this->summaryPrompt->getPrompt($this->book, $this->user),
            'tags' => $this->tagPrompt->getPrompt($this->book, $this->user),
            default => throw new \InvalidArgumentException('Invalid field'),
        };
    }

    #[LiveAction]
    public function generate(): void
    {
        $this->suggestions = self::EMPTY_SUGGESTIONS;

        if ($this->user->getOpenAIKey() === null) {
            return;
        }

        $open_ai = new OpenAi($this->user->getOpenAIKey());

        $chat = $open_ai->chat([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful factual librarian that only refers to verifiable content to provide real answers about existing books.',
                ],
                [
                    'role' => 'user',
                    'content' => $this->prompt,
                ],
            ],
            'temperature' => 0,
            'max_tokens' => 4000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ]);

        if (!is_string($chat)) {
            throw new \RuntimeException('Failed to decode OpenAI response');
        }
        $jsonResult = json_decode($chat);
        // @phpstan-ignore-next-line
        $apiResult = $jsonResult->choices[0]->message->content;

        $this->result = match ($this->field) {
            'tags' => $this->tagPrompt->promptResultToTags($apiResult),
            default => [trim($apiResult)],
        };

        $this->suggestions[$this->field] = $this->result;
    }
}
