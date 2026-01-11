<?php

namespace App\Tests\Controller\Kobo\Api\V1;

use App\DataFixtures\KoboFixture;
use App\Entity\KoboDevice;
use App\Tests\Contraints\JSONContainKeys;
use App\Tests\Controller\Kobo\KoboControllerTestCase;
use Symfony\Component\BrowserKit\Response;

class InitializationControllerTest extends KoboControllerTestCase
{
    /**
     * @throws \JsonException
     */
    public function testInitializationWith403Proxy(): void
    {
        $client = static::getClient();

        $this->enableRemoteSync();
        $this->getKoboStoreProxy()->setClient($this->getMockClient('Access Denied', 403));

        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/initialization');

        self::assertResponseIsSuccessful();

        self::assertThat(self::getJsonResponse(), new JSONContainKeys(['image_url_template'], 'Resources'), 'Response does not contain all expected keys');
    }

    /**
     * @throws \JsonException
     */
    public function testInitializationWithProxy(): void
    {
        $client = static::getClient();

        $this->enableRemoteSync();
        $this->getKoboStoreProxy()->setClient($this->getMockClient('{
          "Resources": {
            "newSetting": "you rocks"
          }}'));

        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/initialization');

        self::assertResponseIsSuccessful();

        self::assertThat(self::getJsonResponse(), new JSONContainKeys(['newSetting'], 'Resources'), 'Response does not contain all expected keys');
    }

    /**
     * @throws \JsonException
     */
    public function testInitialization(): void
    {
        $client = static::getClient();

        $client?->request('GET', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/initialization');

        /** @var Response|null $response */
        $response = $client?->getResponse();
        if ($response !== null && $response->getStatusCode() === 401) {
            self::markTestSkipped('Kobo initialized via Proxy without credentials');
        }
        self::assertResponseIsSuccessful();
        self::assertResponseHasHeader(KoboDevice::KOBO_API_TOKEN);

        $content = file_get_contents(__DIR__.'/InitializationControllerTest.json');
        if ($content === false) {
            self::fail('Unable to read test data');
        }
        /** @var array<string|int, array|mixed>|false $expected */
        $expected = json_decode($content, true);
        if (false === $expected) {
            self::fail('Unable to decode test data');
        }

        $expectedKeys = array_keys((array) ($expected['Resources'] ?? []));

        self::assertThat(self::getJsonResponse(), new JSONContainKeys($expectedKeys, 'Resources'), 'Response does not contain all expected keys');
    }
}
