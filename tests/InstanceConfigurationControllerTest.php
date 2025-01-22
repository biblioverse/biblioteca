<?php

use App\DataFixtures\UserFixture;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class InstanceConfigurationControllerTest extends WebTestCase
{
    #[Override]
    public function setUp(): void
    {
    }

    public function testInstanceConfiguration(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        self::assertInstanceOf(UserRepository::class, $userRepository);

        $testUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);

        self::assertInstanceOf(UserInterface::class, $testUser);

        $client->loginUser($testUser);

        $crawler = $client->request(Request::METHOD_GET, '/configuration');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('BOOK_FOLDER_NAMING_FORMAT', $crawler->text());

        $client->clickLink('Edit value');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
        $client->submitForm('Update', [
            'instance_configuration[value]' => 'my token',
        ]);
        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('my token', $crawler->text());
    }
}
