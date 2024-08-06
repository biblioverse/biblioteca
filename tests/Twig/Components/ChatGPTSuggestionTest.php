<?php

namespace App\Tests\Twig\Components;

use App\Entity\Book;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use App\Suggestion\SummaryPrompt;
use App\Suggestion\TagPrompt;
use App\Twig\Components\ChatGPTSuggestion;
use App\DataFixtures\BookFixture;
use App\DataFixtures\UserFixture;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class ChatGPTSuggestionTest extends WebTestCase
{
    use InteractsWithLiveComponents;
    private function testGenericPrompt(string $field, string $expectedPrompt): void
    {
        $client = self::createClient();
        $client->loginUser($this->getUser());

        $testComponent = $this->createLiveComponent(
            name: ChatGPTSuggestion::class,
            data: [
                    'book' => $this->getBook(),
                    "field" => $field,
                    "prompt" => 'My Prompt',
            ],
            client: $client
        );

        self::assertStringContainsString($expectedPrompt, $testComponent->render());
    }

    public function testTagPrompt(): void
    {
        // Take the default prompt and remove the {book} part and everything after it
        $bookPlaceholderPosition = strpos(TagPrompt::DEFAULT_KEYWORD_PROMPT, '{book}');
        self::assertNotFalse($bookPlaceholderPosition);
        $expectedPrompt = substr(TagPrompt::DEFAULT_KEYWORD_PROMPT, 0, $bookPlaceholderPosition);

        $this->testGenericPrompt('tags', $expectedPrompt);
    }

    public function testSummaryPrompt(): void
    {

        // Take the default prompt and remove the {book} part and everything after it
        $bookPlaceholderPosition = strpos(SummaryPrompt::DEFAULT_KEYWORD_PROMPT, '{book}');
        self::assertNotFalse($bookPlaceholderPosition);
        $expectedPrompt = substr(SummaryPrompt::DEFAULT_KEYWORD_PROMPT, 0, $bookPlaceholderPosition);

        $this->testGenericPrompt('summary', $expectedPrompt);
    }

    private function getUser(?string $username = null): User
    {
        /** @var UserRepository $repo */
        $repo = self::getContainer()->get(UserRepository::class);
        $user = $repo->findOneBy(['username' => $username ?? UserFixture::USER_USERNAME]);
        assert($user instanceof User);

        return $user;
    }

    private function getBook(): Book
    {
        /** @var BookRepository $repo */
        $repo = self::getContainer()->get(BookRepository::class);

        $book = $repo->findOneBy(["id" => BookFixture::ID]);
        assert($book instanceof Book);

        return $book;
    }
}