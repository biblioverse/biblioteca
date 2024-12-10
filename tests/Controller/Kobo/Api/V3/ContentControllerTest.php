<?php

namespace App\Tests\Controller\Kobo\Api\V3;

use App\Tests\Controller\Kobo\AbstractKoboControllerTest;
use Symfony\Component\HttpFoundation\Response;

class ContentControllerTest extends AbstractKoboControllerTest
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
