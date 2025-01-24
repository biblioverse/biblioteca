<?php

namespace App\Tests\Controller\Kobo\Api\V3;

use App\Tests\Controller\Kobo\KoboControllerTestCase;
use Symfony\Component\HttpFoundation\Response;

class ContentControllerTest extends KoboControllerTestCase
{
    public function testCheckForChangesPost(): void
    {
        $client = static::getClient();

        $client?->request('POST', '/api/v3/content/checkforchanges');
        self::assertResponseIsSuccessful();

        /** @var Response|null $response */
        $response = $client?->getResponse();

        $responseContent = $response?->getContent();
        self::assertSame('[]', $responseContent);
    }
}
