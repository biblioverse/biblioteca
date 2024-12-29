<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class SmokeTest extends WebTestCase
{
    public function testSomething(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        if (!$userRepository instanceof UserRepository) {
            self::fail('UserRepository not found');
        }

        $testUser = $userRepository->findOneBy(['username' => 'admin@example.com']);

        if (!$testUser instanceof UserInterface) {
            self::fail('User not found');
        }

        $client->loginUser($testUser);

        foreach ($this->getUrlList() as $url) {
            $client->request(Request::METHOD_GET, $url);
            self::assertResponseIsSuccessful($url);
        }
    }

    private function getUrlList(): array
    {
        return [
            '/',
            '/reading-list',
            '/all',
            '/user/',
            '/user/1/edit',
            '/user/kobo/',
            '/user/kobo/1/edit',
            '/user/kobo/logs',
            '/user/profile',
            '/shelves/crud/',
            '/shelves/crud/1/edit',
            '/books/new/consume/files',
            '/books/new/consume/upload',
            '/groups/serie',
            '/groups/author',
            '/groups/author/h',
            '/groups/tags',
            '/groups/publisher',
            '/books/1/the-odyssey',
        ];
    }
}
