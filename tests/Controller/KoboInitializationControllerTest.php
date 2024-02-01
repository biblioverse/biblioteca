<?php

namespace App\Tests\Controller;

use App\Tests\Contraints\JSONContainKeys;

class KoboInitializationControllerTest  extends AbstractKoboControllerTest
{

    /**
     * @throws \JsonException
     */
    public function testInitialization() : void
    {
        $client = static::getClient();

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/initialization');

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