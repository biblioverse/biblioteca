<?php

namespace App\Tests;

use App\DataFixtures\UserFixture;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class LoginTest extends WebTestCase
{
    public function testLogin(): void
    {
        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/');

        self::assertResponseRedirects('/login');
        $client->followRedirect();

        $client->submitForm('Sign in', ['_username' => UserFixture::USER_USERNAME, '_password' => UserFixture::USER_PASSWORD]);

        $client->followRedirect();

        $client->request(Request::METHOD_GET, '/');
        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('h1', 'Aloha '.UserFixture::USER_USERNAME.'!');
    }
}
