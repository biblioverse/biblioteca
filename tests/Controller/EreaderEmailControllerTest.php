<?php

namespace App\Tests\Controller;

use App\DataFixtures\UserFixture;
use App\Entity\EreaderEmail;
use App\Repository\EreaderEmailRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class EreaderEmailControllerTest extends WebTestCase
{
    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/user/ereader-email/');

        self::assertResponseRedirects();
    }

    public function testIndexShowsUserEreaderEmails(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);

        self::assertInstanceOf(UserInterface::class, $testUser);
        $client->loginUser($testUser);

        $client->request(Request::METHOD_GET, '/user/ereader-email/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'E-reader Email Settings');
    }

    public function testNewEreaderEmail(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);

        self::assertInstanceOf(UserInterface::class, $testUser);
        $client->loginUser($testUser);

        $crawler = $client->request(Request::METHOD_GET, '/user/ereader-email/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form([
            'ereader_email[name]' => 'My Kindle',
            'ereader_email[email]' => 'kindle@example.com',
        ]);

        $client->submit($form);
        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'E-reader email added successfully');

        // Verify the email was saved
        $ereaderEmailRepository = static::getContainer()->get(EreaderEmailRepository::class);
        $ereaderEmails = $ereaderEmailRepository->findAllByUser($testUser);
        self::assertCount(1, $ereaderEmails);
        self::assertEquals('My Kindle', $ereaderEmails[0]->getName());
        self::assertEquals('kindle@example.com', $ereaderEmails[0]->getEmail());
    }

    public function testEditEreaderEmail(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);

        self::assertInstanceOf(UserInterface::class, $testUser);
        $client->loginUser($testUser);

        // Create an ereader email first
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $ereaderEmail = $em->getRepository(EreaderEmail::class)->findOneBy(['user' => $testUser]) ?? new EreaderEmail();
        $ereaderEmail->setName('Original Name');
        $ereaderEmail->setEmail('original@example.com');
        $ereaderEmail->setUser($testUser);
        $em->persist($ereaderEmail);
        $em->flush();

        $crawler = $client->request(Request::METHOD_GET, '/user/ereader-email/'.$ereaderEmail->getId().'/edit');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form([
            'ereader_email[name]' => 'Updated Name',
            'ereader_email[email]' => 'updated@example.com',
        ]);

        $client->submit($form);
        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'E-reader email updated successfully');

        // Verify the email was updated
        $updatedEreaderEmail = $em->getRepository(EreaderEmail::class)->find($ereaderEmail->getId());
        self::assertNotNull($updatedEreaderEmail);
        self::assertEquals('Updated Name', $updatedEreaderEmail->getName());
        self::assertEquals('updated@example.com', $updatedEreaderEmail->getEmail());
    }

    public function testDeleteEreaderEmail(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);

        self::assertInstanceOf(UserInterface::class, $testUser);
        $client->loginUser($testUser);

        // Create an ereader email first
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $ereaderEmail = new EreaderEmail();
        $ereaderEmail->setName('To Delete');
        $ereaderEmail->setEmail('delete@example.com');
        $ereaderEmail->setUser($testUser);
        $em->persist($ereaderEmail);
        $em->flush();
        $ereaderEmailId = $ereaderEmail->getId();

        $crawler = $client->request(Request::METHOD_GET, '/user/profile?tab=ereader');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[action*="'.$ereaderEmailId.'"]')->form();
        $client->submit($form);
        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'E-reader email deleted successfully');

        // Verify the email was deleted
        $ereaderEmailRepository = static::getContainer()->get(EreaderEmailRepository::class);
        $deletedEmail = $ereaderEmailRepository->find($ereaderEmailId);
        self::assertNull($deletedEmail);
    }

    public function testCannotEditOtherUserEreaderEmail(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['username' => UserFixture::CHILD_USERNAME]);

        self::assertInstanceOf(UserInterface::class, $testUser);
        $client->loginUser($testUser);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $otherUser = $userRepository->findOneBy(['username' => 'other@example.com']);
        self::assertNotNull($otherUser);

        // Create an ereader email for the other user
        $ereaderEmail = $em->getRepository(EreaderEmail::class)->findOneBy(['user' => $otherUser]) ?? new EreaderEmail();
        $ereaderEmail->setName('Other User Email');
        $ereaderEmail->setEmail('other@kindle.com');
        $ereaderEmail->setUser($otherUser);
        $em->persist($ereaderEmail);
        $em->flush();

        // Try to edit it
        $client->request(Request::METHOD_GET, '/user/ereader-email/'.$ereaderEmail->getId().'/edit');
        self::assertResponseStatusCodeSame(403);
    }
}
