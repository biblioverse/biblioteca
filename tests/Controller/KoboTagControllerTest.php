<?php

namespace App\Tests\Controller;

use App\DataFixtures\ShelfFixture;
use App\Entity\Shelf;

class KoboTagControllerTest extends AbstractKoboControllerTest
{
    public function testDelete() : void
    {
        $client = static::getClient();

        $shelf = $this->getShelfByName(ShelfFixture::SHELF_NAME);
        self::assertNotNull($shelf, 'shelf '.ShelfFixture::SHELF_NAME.' not found');

        self::assertTrue($this->getKoboDevice()->getShelves()->contains($shelf), 'Shelf should be associated with Kobo');

        $client?->request('DELETE', '/kobo/'.$this->accessKey.'/v1/library/tags/'.$shelf->getUuid());

        self::assertResponseIsSuccessful();
        self::assertFalse($this->getKoboDevice(true)->getShelves()->contains($shelf), 'Shelf should NOT be associated with Kobo');



        // Re-add the shelf to the Kobo
        $shelf->addKoboDevice($this->getKoboDevice());
        $this->getEntityManager()->flush();
    }

    protected function getShelfByName(string $name): ?Shelf
    {
        $shelf = $this->getEntityManager()->getRepository(Shelf::class)->findOneBy(['name' => $name]);

        /** @var Shelf|null */
        return $shelf;
    }


}