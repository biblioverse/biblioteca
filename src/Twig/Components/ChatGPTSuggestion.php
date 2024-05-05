<?php

namespace App\Twig\Components;

use App\Entity\Book;
use App\Entity\User;
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

    public array|string $result = '';
    public array $suggestions = [];

    public const EMPTY_SUGGESTIONS = [
        'image' => [],
        'title' => [],
        'authors' => [],
        'publisher' => [],
        'tags' => [],
        'summary' => [],
    ];

    public function __construct(private Security $security)
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('User must be logged in');
        }
        $this->user = $user;
    }

    #[PostMount]
    public function postMount(): void
    {
        $this->prompt = (string) match ($this->field.'') {
            'summary' => $this->user->getBookSummaryPrompt(),
            'tags' => $this->user->getBookKeywordPrompt(),
            default => throw new \InvalidArgumentException('Invalid field'),
        };

        $bookString = '"'.$this->book->getTitle().'" by '.implode(' and ', $this->book->getAuthors());

        if ($this->book->getSerie() !== null) {
            $bookString .= ' number '.$this->book->getSerieIndex().' in the series "'.$this->book->getSerie().'"';
        }

        $this->prompt = str_replace('{book}', $bookString, $this->prompt);
    }

    #[LiveAction]
    public function generate(): void
    {
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
        $d = json_decode($chat);
        // @phpstan-ignore-next-line
        $this->result = $d->choices[0]->message->content;

        if ($this->field === 'tags') {
            $this->result = explode("\n", $this->result);
            foreach ($this->result as $key => $value) {
                if (str_starts_with($value, '- ')) {
                    $this->result[$key] = trim($value, " \n\r\t\v\0-");
                }
            }
        } else {
            $this->result = trim($this->result);
        }

        $this->suggestions = self::EMPTY_SUGGESTIONS;
        $this->suggestions[$this->field] = is_array($this->result) ? $this->result : [$this->result];
    }
}
