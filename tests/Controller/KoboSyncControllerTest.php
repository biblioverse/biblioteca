<?php

namespace App\Tests\Controller;

use App\Entity\KoboSyncedBook;
use App\Tests\Contraints\JSONIsValidSyncResponse;

class KoboSyncControllerTest extends AbstractKoboControllerTest
{

    public function assertPreConditions(): void
    {
        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['kobo' => 1]);
        self::assertSame(0, $count, 'There should be no synced books');
    }

    /**
     * @throws \JsonException
     */
    public function testSyncControllerWithForce() : void
    {
        $client = static::getClient();

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/library/sync?force=1');

        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);


        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new JSONIsValidSyncResponse([
            'NewEntitlement' => 1,
            'NewTag' => 1
        ]), 'Response is not a valid sync response');

        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['kobo' => 1]);
        self::assertSame(0, $count, 'There should be no synced books');
    }

    public function testSyncControllerWithoutForce() : void
    {
        $client = static::getClient();

        $client?->request('GET', '/kobo/'.$this->accessKey.'/v1/library/sync');

        $response = self::getJsonResponse();
        self::assertResponseIsSuccessful();
        self::assertThat($response, new JSONIsValidSyncResponse([
            'NewEntitlement' => 1,
            'NewTag' => 1
        ]), 'Response is not a valid sync response');

        $count = $this->getEntityManager()->getRepository(KoboSyncedBook::class)->count(['kobo' => 1]);
        self::assertSame(1, $count, 'There should be 1 synced books');

        $this->getEntityManager()->getRepository(KoboSyncedBook::class)->deleteAllSyncedBooks(1);

    }
}