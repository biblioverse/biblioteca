<?php

namespace App\Tests\Controller\Opds;

use App\DataFixtures\OpdsAccessFixture;
use App\DataFixtures\UserFixture;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class OpdsAccessControllerTest extends AbstractOpdsTestController
{
    public function testOpds(): void
    {
        $client = static::getClient();

        $this->ensureFixtureExists();
        $client?->request(Request::METHOD_GET, '/opds/'.OpdsAccessFixture::ACCESS_KEY.'/');

        $response = static::getXmlResponse();
        self::assertResponseIsSuccessful();

        self::assertArrayHasKey('entry', $response);

        $entry = $response['entry'][0];

        self::assertArrayHasKey('title', $entry);
        self::assertEquals('Series', $entry['title']);
    }

    public function testOpdsAuthors(): void
    {
        $this->ensureFixtureExists();
        $client = static::getClient();
        $client?->request(Request::METHOD_GET, '/opds/'.OpdsAccessFixture::ACCESS_KEY.'/group/authors');

        $response = static::getXmlResponse();
        self::assertResponseIsSuccessful();

        self::assertArrayHasKey('entry', $response);

        $entry = $response['entry'][0];

        self::assertArrayHasKey('title', $entry);
        self::assertEquals('Alexandre Dumas', $entry['title']);
    }

    public function testOpdsNoAccess(): void
    {
        $this->ensureFixtureExists();
        $client = static::getClient();
        $client?->request(Request::METHOD_GET, '/opds/not-valid');

        self::assertResponseStatusCodeSame(401);
    }

    public function testAccessCreation(): void
    {
        $client = static::getClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        self::assertInstanceOf(UserRepository::class, $userRepository);

        $testUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);

        self::assertInstanceOf(UserInterface::class, $testUser);

        self::assertInstanceOf(KernelBrowser::class, $client);

        $client->loginUser($testUser);

        $client->request(Request::METHOD_GET, '/user/profile?tab=opds');
        self::assertResponseIsSuccessful();

        self::assertSelectorExists('.Card__content > strong', 'Check that no key is present');

        $client->clickLink('Remove token');

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        $client->request(Request::METHOD_GET, '/user/profile?tab=opds');
        self::assertResponseIsSuccessful();

        $client->clickLink('Create');

        $client->request(Request::METHOD_GET, '/user/profile?tab=opds');
        self::assertResponseIsSuccessful();

        self::assertSelectorExists('.Card__content > strong', 'Check that a key was generated');
    }
}
