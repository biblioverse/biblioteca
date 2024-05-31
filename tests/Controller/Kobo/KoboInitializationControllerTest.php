<?php

namespace App\Tests\Controller\Kobo;

use App\Tests\Contraints\JSONContainKeys;
use Symfony\Component\BrowserKit\Response;

class KoboInitializationControllerTest  extends AbstractKoboControllerTest
{

    /**
     * @throws \JsonException
     */
    public function testInitialization() : void
    {
        $client = static::getClient();

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/initialization');

        /** @var Response|null $response */
        $response = $client?->getResponse();
        if($response !== null && $response->getStatusCode() === 401){
            self::markTestSkipped('Kobo initialized via Proxy without credentials');
        }
        self::assertResponseIsSuccessful();
        self::assertResponseHasHeader('kobo-api-token');

        $content = file_get_contents(__DIR__.'/KoboInitializationControllerTest.json');
        if($content === false) {
            self::fail('Unable to read test data');
        }
        /** @var array<string|int, array|mixed>|false $expected */
        $expected = json_decode($content, true);
        if(false === $expected) {
            self::fail('Unable to decode test data');
        }

        $expectedKeys = array_keys((array)($expected['Resources']??[]));

        self::assertThat(self::getJsonResponse(), new JSONContainKeys($expectedKeys, 'Resources'), 'Response does not contain all expected keys');


    }
}