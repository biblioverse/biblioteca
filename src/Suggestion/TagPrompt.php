<?php

namespace App\Suggestion;

use App\Entity\Book;
use App\Entity\User;
use Orhanerday\OpenAi\OpenAi;
use Psr\Log\LoggerInterface;

class TagPrompt extends AbstractBookPrompt
{
    public function __construct(private LoggerInterface $logger)
    {
    }
    public const DEFAULT_KEYWORD_PROMPT = 'Imagine you want to tag a book. Can you cite the most probable categories for the following book: {book}? Display only the results, separating each term with a newline, and return an empty string if you don\'t know. This way, your answer can be parsed.';

    /**
     * @return array<string>
     */
    public function promptResultToTags(string $promptResult): array
    {
        $tags = explode("\n", $promptResult);
        $result = [];
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (str_starts_with($tag, '- ')) {
                $tag = ltrim($tag, '- ');
            }
            $result[] = $tag;
        }

        return array_filter($result, fn ($tag) => $tag !== '');
    }

    public function getPrompt(Book $book, User $user): string
    {
        $prompt = $user->getBookKeywordPrompt() ?? self::DEFAULT_KEYWORD_PROMPT;

        return $this->replaceBookOccurrence($book, $prompt);
    }

    /**
     * @throws \InvalidArgumentException When the user has no api key
     * @throws \Exception
     */
    public function generateTags(Book $book, User $user): void
    {
        if ($user->getOpenAIKey() === null) {
            throw new \InvalidArgumentException('User does not have an OpenAI Key');
        }

        $open_ai = new OpenAi($user->getOpenAIKey());

        $chat = $open_ai->chat([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful factual librarian that only refers to verifiable content to provide real answers about existing books.',
                ],
                [
                    'role' => 'user',
                    'content' => $this->getPrompt($book, $user),
                ],
            ],
            'temperature' => 0,
            'max_tokens' => 4000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ]);

        if (!is_string($chat)) {
            $this->logger->error('Failed to decode OpenAI response');

            return;
        }
        $d = json_decode($chat);
        // @phpstan-ignore-next-line
        $result = $d->choices[0]->message->content;

        foreach ($this->promptResultToTags($result) as $tag) {
            $book->addTag($tag);
        }
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}
