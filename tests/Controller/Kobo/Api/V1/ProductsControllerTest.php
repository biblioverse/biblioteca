<?php

namespace App\Tests\Controller\Kobo\Api\V1;

use App\DataFixtures\BookFixture;
use App\DataFixtures\KoboFixture;
use App\Tests\Controller\Kobo\AbstractKoboControllerTest;

class ProductsControllerTest extends AbstractKoboControllerTest
{
    public function testNextRead(): void
    {
        $unknownUuid = str_replace('0', 'b', BookFixture::UUID_JUNGLE_BOOK);

        $client = self::getClient();
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/products/'.$unknownUuid.'/nextread');
        self::assertResponseIsSuccessful();
    }
}
