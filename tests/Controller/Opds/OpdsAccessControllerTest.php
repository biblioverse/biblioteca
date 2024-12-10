<?php

namespace App\Tests\Controller\Opds;

use App\DataFixtures\OpdsAccessFixture;
use Symfony\Component\HttpFoundation\Request;

class OpdsAccessControllerTest extends AbstractOpdsTestController
{
    public function testOpds(): void
    {
        $client = static::getClient();
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
        $client = static::getClient();
        $client?->request(Request::METHOD_GET, '/opds/not-valid');

        self::assertResponseStatusCodeSame(401);
    }
}
