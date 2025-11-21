<?php

namespace App\Tests\Controller;

use App\DataFixtures\BookFixture;
use App\DataFixtures\UserFixture;
use App\Entity\Book;
use App\Entity\EreaderEmail;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\EreaderEmailRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class BookControllerEreaderTest extends WebTestCase
{
    public function testBookPageShowsEreaderButtons(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);

        self::assertInstanceOf(UserInterface::class, $testUser);
        $client->loginUser($testUser);

        // Create an ereader email
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $ereaderEmail = $em->getRepository(EreaderEmail::class)->findOneBy(['user' => $testUser]) ?? new EreaderEmail();
        $ereaderEmail->setName('My Kindle');
        $ereaderEmail->setEmail('kindle@example.com');
        $ereaderEmail->setUser($testUser);
        $em->persist($ereaderEmail);
        $em->flush();

        $bookRepository = static::getContainer()->get(BookRepository::class);
        $book = $bookRepository->find(BookFixture::ID);

        self::assertInstanceOf(Book::class, $book);

        $client->request(Request::METHOD_GET, '/books/'.$book->getId().'/'.$book->getSlug());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Send to My Kindle');
    }

    public function testBookPageDoesNotShowButtonsWhenNoEreaderEmails(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);

        self::assertInstanceOf(UserInterface::class, $testUser);
        $client->loginUser($testUser);

        // Remove all ereader emails
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $ereaderEmailRepository = static::getContainer()->get(EreaderEmailRepository::class);
        $ereaderEmails = $ereaderEmailRepository->findAllByUser($testUser);
        foreach ($ereaderEmails as $email) {
            $em->remove($email);
        }
        $em->flush();

        $bookRepository = static::getContainer()->get(BookRepository::class);
        $book = $bookRepository->find(BookFixture::ID);

        self::assertInstanceOf(Book::class, $book);

        $client->request(Request::METHOD_GET, '/books/'.$book->getId().'/'.$book->getSlug());
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('button:contains("Send to")');
    }

    public function testCannotSendToOtherUserEreaderEmail(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);

        self::assertInstanceOf(UserInterface::class, $testUser);
        $client->loginUser($testUser);

        // Create another user
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $otherUser = $userRepository->findOneBy(['username' => 'other@example.com']) ?? new User();
        $otherUser->setUsername('other@example.com');
        $otherUser->setPassword('hashed');
        $otherUser->setRoles(['ROLE_USER']);
        $em->persist($otherUser);
        $em->flush();

        // Create an ereader email for the other user
        $ereaderEmail = $em->getRepository(EreaderEmail::class)->findOneBy(['user' => $otherUser]) ?? new EreaderEmail();
        $ereaderEmail->setName('Other User Email');
        $ereaderEmail->setEmail('other@kindle.com');
        $ereaderEmail->setUser($otherUser);
        $em->persist($ereaderEmail);
        $em->flush();

        $bookRepository = static::getContainer()->get(BookRepository::class);
        $book = $bookRepository->find(BookFixture::ID);

        self::assertInstanceOf(Book::class, $book);

        // Try to send to the other user's ereader email
        $client->request(Request::METHOD_POST, '/books/'.$book->getId().'/'.$book->getSlug().'/send-to-ereader/'.$ereaderEmail->getId());

        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'You are not allowed to use this e-reader email');
    }
}
