<?php

namespace App\Tests;

use App\DataFixtures\UserFixture;
use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class SmokeTest extends WebTestCase
{
    public function testBookPage(): void
    {
        $client = static::createClient();
        $bookRepository = static::getContainer()->get(BookRepository::class);

        self::assertInstanceOf(BookRepository::class, $bookRepository);

        $book = $bookRepository->findOneBy(['title' => 'Moby Dick']);

        self::assertInstanceOf(Book::class, $book);

        $userRepository = static::getContainer()->get(UserRepository::class);

        self::assertInstanceOf(UserRepository::class, $userRepository);

        $testUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);
        $childUser = $userRepository->findOneBy(['username' => UserFixture::CHILD_USERNAME]);

        self::assertInstanceOf(UserInterface::class, $testUser);
        self::assertInstanceOf(UserInterface::class, $childUser);

        $client->loginUser($testUser);

        $client->request(Request::METHOD_GET, '/books/'.$book->getId().'/'.$book->getSlug());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $book->getTitle());

        $client->request(Request::METHOD_GET, '/books/'.$book->getId().'/aaaaaaaaaa');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $book->getTitle());

        $client->request(Request::METHOD_GET, '/books/'.$book->getId().'/'.$book->getSlug().'/read');
        self::assertResponseIsSuccessful();

        $client->loginUser($childUser);

        $client->request(Request::METHOD_GET, '/books/'.$book->getId().'/'.$book->getSlug());
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Aloha');
    }

    public function testUrls(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        self::assertInstanceOf(UserRepository::class, $userRepository);

        $testUser = $userRepository->findOneBy(['username' => 'admin@example.com']);

        self::assertInstanceOf(UserInterface::class, $testUser);

        $client->loginUser($testUser);

        foreach ($this->getUrlList() as $url => $params) {
            $client->request(Request::METHOD_GET, $url);
            if (array_key_exists('redirect', $params) && $params['redirect'] === true) {
                $client->followRedirect();
            }
            self::assertResponseIsSuccessful($url);
        }
    }

    private function getUrlList(): array
    {
        return [
            '/' => [],
            '/reading-list' => [],
            '/all' => [],
            '/user/' => [],
            '/user/1/edit' => [],
            '/user/kobo/' => [],
            '/user/kobo/1/edit' => [],
            '/user/kobo/logs' => [],
            '/user/profile' => [],
            '/user/profile?tab=opds' => [],
            '/user/profile?tab=kobo' => [],
            '/shelves/crud/' => [],
            '/shelves/crud/1/edit' => [],
            '/shelf/test-shelf' => [],
            '/configuration' => [],
            '/books/new/consume/files' => [],
            '/books/new/consume/upload' => [],
            '/groups/serie' => [],
            '/groups/author' => [],
            '/groups/author/h' => [],
            '/groups/tags' => [],
            '/groups/publisher' => [],
            '/books/1/the-odyssey' => [],
            '/not-verified' => [],
            '/not-verified?action=validate' => ['redirect' => true],
            '/not-verified?action=relocate' => ['redirect' => true],
            '/not-verified?action=extract' => ['redirect' => true],
            '/timeline' => ['redirect' => true],
            '/timeline/comic' => ['redirect' => true],
            '/timeline/book/2023' => [],
            '/autocomplete/group/serie' => [],
            '/autocomplete/group/authors' => [],
            '/autocomplete/group/tags' => [],
            '/autocomplete/group/publisher' => [],
            '/autocomplete/group/ageCategory' => [],
            '/autocomplete/group/serie?query=a' => [],
            '/autocomplete/group/authors?query=a' => [],
            '/autocomplete/group/tags?query=a' => [],
            '/autocomplete/group/publisher?query=a' => [],
        ];
    }
}
