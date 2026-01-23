<?php

namespace App\Tests\Controller;

use App\DataFixtures\UserFixture;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class DefaultControllerTest extends WebTestCase
{
    public function testNotVerifiedSortingDefaultsToPathAsc(): void
    {
        $client = static::createClient();

        // Login as admin
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);
        self::assertInstanceOf(UserInterface::class, $adminUser);
        $client->loginUser($adminUser);

        // First, we need to create some unverified books
        $bookRepository = static::getContainer()->get(BookRepository::class);
        $em = static::getContainer()->get('doctrine.orm.entity_manager');

        // Mark some books as unverified
        $books = $bookRepository->findBy([], null, 3);
        foreach ($books as $book) {
            $book->setVerified(false);
            $em->persist($book);
        }
        $em->flush();

        // Access notverified page without sort parameters
        $client->request(Request::METHOD_GET, '/not-verified');
        self::assertResponseIsSuccessful();

        // Check that default sorting is applied (path asc)
        // Path should be the active sort with up chevron (asc)
        $crawler = $client->getCrawler();

        // Debug: Check if we have the sorting links
        $sortingLinks = $crawler->filter('th a');
        self::assertGreaterThan(0, $sortingLinks->count(), 'Should have sorting links in table header');

        // Find the Path link specifically
        $pathLink = $crawler->filter('a')->reduce(fn ($node) => str_contains($node->text(), 'Path'));

        self::assertCount(1, $pathLink, 'Should have exactly one Path sorting link');
        self::assertStringContainsString('bi-chevron-up', $pathLink->html(), 'Path should show ascending order indicator');
    }

    public function testNotVerifiedSortingByTitle(): void
    {
        $client = static::createClient();

        // Login as admin
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);
        self::assertInstanceOf(UserInterface::class, $adminUser);
        $client->loginUser($adminUser);

        // Create some unverified books
        $bookRepository = static::getContainer()->get(BookRepository::class);
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $books = $bookRepository->findBy([], null, 3);
        foreach ($books as $book) {
            $book->setVerified(false);
            $em->persist($book);
        }
        $em->flush();

        // Access notverified page with title sorting
        $client->request(Request::METHOD_GET, '/not-verified', ['sort' => 'title', 'order' => 'desc']);
        self::assertResponseIsSuccessful();

        // Check that title sorting is applied
        // Title should be the active sort with down chevron (desc)
        $crawler = $client->getCrawler();
        $titleLink = $crawler->filter('a:contains("Title")');
        self::assertGreaterThan(0, $titleLink->count());
        self::assertStringContainsString('bi-chevron-down', $titleLink->first()->html());

        // Check that cookies are set
        $response = $client->getResponse();
        $cookies = $response->headers->getCookies();
        $sortCookie = null;
        $orderCookie = null;

        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'notverified_sort') {
                $sortCookie = $cookie;
            }
            if ($cookie->getName() === 'notverified_order') {
                $orderCookie = $cookie;
            }
        }

        self::assertNotNull($sortCookie, 'Sort cookie should be set');
        self::assertEquals('title', $sortCookie->getValue());

        self::assertNotNull($orderCookie, 'Order cookie should be set');
        self::assertEquals('desc', $orderCookie->getValue());
    }

    public function testNotVerifiedSortingBySerie(): void
    {
        $client = static::createClient();

        // Login as admin
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);
        self::assertInstanceOf(UserInterface::class, $adminUser);
        $client->loginUser($adminUser);

        // Create some unverified books
        $bookRepository = static::getContainer()->get(BookRepository::class);
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $books = $bookRepository->findBy([], null, 3);
        foreach ($books as $book) {
            $book->setVerified(false);
            $em->persist($book);
        }
        $em->flush();

        // Access notverified page with serie sorting
        $client->request(Request::METHOD_GET, '/not-verified', ['sort' => 'serie', 'order' => 'asc']);
        self::assertResponseIsSuccessful();

        // Check that serie sorting is applied
        // Serie should be the active sort with up chevron (asc)
        $crawler = $client->getCrawler();
        // Look specifically in the table header for the Serie sorting link
        $serieLink = $crawler->filter('th a')->reduce(fn ($node) => str_contains($node->text(), 'Serie'));
        self::assertCount(1, $serieLink, 'Should have exactly one Serie sorting link in table header');
        self::assertStringContainsString('bi-chevron-up', $serieLink->html());
    }

    public function testNotVerifiedSortingFromCookies(): void
    {
        $client = static::createClient();

        // Login as admin
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);
        self::assertInstanceOf(UserInterface::class, $adminUser);
        $client->loginUser($adminUser);

        // Create some unverified books
        $bookRepository = static::getContainer()->get(BookRepository::class);
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $books = $bookRepository->findBy([], null, 3);
        foreach ($books as $book) {
            $book->setVerified(false);
            $em->persist($book);
        }
        $em->flush();

        // First request to set cookies
        $client->request(Request::METHOD_GET, '/not-verified', ['sort' => 'title', 'order' => 'desc']);
        self::assertResponseIsSuccessful();

        // Second request without parameters should use cookies
        $client->request(Request::METHOD_GET, '/not-verified');
        self::assertResponseIsSuccessful();

        // Check that cookie values are used (title desc)
        $crawler = $client->getCrawler();
        $titleLink = $crawler->filter('a:contains("Title")');
        self::assertGreaterThan(0, $titleLink->count());
        self::assertStringContainsString('bi-chevron-down', $titleLink->first()->html());
    }

    public function testNotVerifiedInvalidSortParameters(): void
    {
        $client = static::createClient();

        // Login as admin
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);
        self::assertInstanceOf(UserInterface::class, $adminUser);
        $client->loginUser($adminUser);

        // Create some unverified books
        $bookRepository = static::getContainer()->get(BookRepository::class);
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $books = $bookRepository->findBy([], null, 3);
        foreach ($books as $book) {
            $book->setVerified(false);
            $em->persist($book);
        }
        $em->flush();

        // Access with invalid sort parameters
        $client->request(Request::METHOD_GET, '/not-verified', ['sort' => 'invalid', 'order' => 'invalid']);
        self::assertResponseIsSuccessful();

        // Should default to path asc
        $crawler = $client->getCrawler();
        $pathLink = $crawler->filter('a:contains("Path")');
        self::assertCount(1, $pathLink);
        self::assertStringContainsString('bi-chevron-up', $pathLink->html());
    }

    public function testNotVerifiedBatchActions(): void
    {
        $client = static::createClient();

        // Login as admin
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);
        self::assertInstanceOf(UserInterface::class, $adminUser);
        $client->loginUser($adminUser);

        // Create some unverified books
        $bookRepository = static::getContainer()->get(BookRepository::class);
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $books = $bookRepository->findBy([], null, 3);
        foreach ($books as $book) {
            $book->setVerified(false);
            $em->persist($book);
        }
        $em->flush();

        // Test validate action
        $client->request(Request::METHOD_GET, '/not-verified', ['action' => 'validate']);
        self::assertResponseRedirects('/not-verified');
        $client->followRedirect();

        // Test extract covers action
        $client->request(Request::METHOD_GET, '/not-verified', ['action' => 'extract']);
        self::assertResponseRedirects('/not-verified');
        $client->followRedirect();
    }
}
