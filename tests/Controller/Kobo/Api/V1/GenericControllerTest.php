<?php

namespace App\Tests\Controller\Kobo\Api\V1;

use App\DataFixtures\KoboFixture;
use App\Tests\Contraints\JSONContainKeys;
use App\Tests\Controller\Kobo\AbstractKoboControllerTest;

class GenericControllerTest extends AbstractKoboControllerTest
{
    public function testRedirect(): void
    {
        $client = self::getClient();
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/products/deals');

        self::assertResponseStatusCodeSame(307);
        self::assertResponseHeaderSame('Location', 'http://storeapi.kobo.com/v1/products/deals');
    }

    public function testBenefits(): void
    {
        $client = self::getClient();
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/user/loyalty/benefits');
        self::assertResponseIsSuccessful();
        self::assertThat(self::getJsonResponse(), new JSONContainKeys(['Benefits']), 'Response does not contain all expected keys');
    }

    public function testBenefitsWithProxy(): void
    {
        $this->enableRemoteSync();

        $client = self::getClient();
        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/user/loyalty/benefits');
        self::assertResponseStatusCodeSame(307);
    }
}
