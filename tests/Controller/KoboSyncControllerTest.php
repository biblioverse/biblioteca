<?php

namespace App\Tests\Controller;

class KoboSyncControllerTest extends AbstractKoboControllerTest

{
    public function testInitialization() : void
    {
        $client = static::getClient();

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/library/sync');

        self::assertResponseIsSuccessful();


    }
}