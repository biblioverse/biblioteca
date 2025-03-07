<?php

namespace App\Tests\Ai;

use App\Ai\Communicator\OllamaCommunicator;
use App\Ai\Communicator\OpenAiCommunicator;
use App\DataFixtures\UserFixture;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class AiModelControllerTest extends WebTestCase
{
    #[\Override]
    public function setUp(): void
    {
    }

    public function testModelConfiguration(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        self::assertInstanceOf(UserRepository::class, $userRepository);

        $testUser = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);

        self::assertInstanceOf(UserInterface::class, $testUser);

        $client->loginUser($testUser);

        $testableModelClasses = [OllamaCommunicator::class, OpenAiCommunicator::class];
        foreach ($testableModelClasses as $modelClass) {
            $client->request(Request::METHOD_GET, '/ai/model');
            self::assertResponseIsSuccessful();
            $client->clickLink('Create new');
            self::assertResponseIsSuccessful();
            self::assertSelectorExists('form');
            $client->submitForm('Save', [
                'ai_model[type]' => $modelClass,
                'ai_model[url]' => 'https://api.mock.internal/',
                'ai_model[model]' => 'mistral-nemo',
                'ai_model[token]' => '',
                'ai_model[systemPrompt]' => 'You are a testful assistant',
            ]);
            $client->followRedirect();
            self::assertResponseIsSuccessful();
            $client->clickLink('Test');
            self::assertResponseIsSuccessful();
            $client->submitForm('Test model', []);
            self::assertResponseIsSuccessful();
            self::assertSelectorExists('#results');
            self::assertSelectorTextContains('#summary-result', 'This is a valid response from the communicator');

            $crawler = $client->request(Request::METHOD_GET, '/ai/model');
            self::assertResponseIsSuccessful();
            $link = $crawler->filter('#js-main')->selectLink('Edit');
            $client->click($link->link());

            self::assertResponseIsSuccessful();
            self::assertSelectorExists('form');

            $client->request(Request::METHOD_GET, '/ai/model');
            $client->clickLink('Delete');
            $client->followRedirect();
            self::assertResponseIsSuccessful();
        }
    }
}
