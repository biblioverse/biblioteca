<?php

namespace App\Tests\Controller\Kobo\Api\V1;

use App\DataFixtures\BookFixture;
use App\DataFixtures\KoboFixture;
use App\DataFixtures\ShelfFixture;
use App\Entity\Shelf;
use App\Kobo\Request\TagDeleteRequest;
use App\Kobo\Request\TagDeleteRequestItem;
use App\Repository\ShelfRepository;
use App\Tests\Controller\Kobo\KoboControllerTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TagControllerTest extends KoboControllerTestCase
{
    private const string SHELF_TEMPORARY = 'delete_me';

    #[\Override]
    public function tearDown(): void
    {
        $this->getService(ShelfRepository::class)
            ->deleteByName(self::SHELF_TEMPORARY);

        $this->getBook()->addShelf($this->getShelf());
        $this->getEntityManager()->flush();

        parent::tearDown();
    }

    public function testDeleteWithProxy(): void
    {
        $client = static::getClient();

        $this->enableRemoteSync();
        $this->getKoboStoreProxy()->setClient($this->getMockClient('-- something --'));

        $client?->request('DELETE', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/tags/'.ShelfFixture::UNKNOWN_UUID);

        self::assertResponseIsSuccessful();
    }

    public function testDeleteItemsWithProxy(): void
    {
        $client = static::getClient();

        $this->enableRemoteSync();
        $this->getKoboStoreProxy()->setClient($this->getMockClient('-- not found --', 404));

        $client?->request('POST', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/tags/'.ShelfFixture::UNKNOWN_UUID.'/items/delete');

        self::assertResponseStatusCodeSame(201);
    }

    public function testDeleteItems(): void
    {
        $shelf = new Shelf();
        $koboDevice = $this->getKoboDevice();
        $shelf->addKoboDevice($koboDevice);
        $shelf->setName(self::SHELF_TEMPORARY);
        $shelf->setUser($koboDevice->getUser());
        $em = $this->getService(EntityManagerInterface::class);
        $em->persist($shelf);

        $book = $this->getBook();
        $shelf->addBook($book);

        $em->flush();

        $request = new TagDeleteRequest();
        $request->items = [
            new TagDeleteRequestItem(BookFixture::UUID),
        ];
        $json = $this->getService(SerializerInterface::class)
            ->serialize($request, 'json');

        $client = static::getClient();
        $client?->request('POST', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/tags/'.$shelf->getUuid().'/items/delete', [], [], [], $json);

        self::assertResponseStatusCodeSame(201);

        $shelf = $this->getShelfByName(self::SHELF_TEMPORARY);
        self::assertNotNull($shelf);
        self::assertSame(0, $shelf->getBooks()->count());
    }

    public function testDelete(): void
    {
        $client = static::getClient();

        $shelf = $this->getShelfByName(ShelfFixture::SHELF_NAME);
        self::assertNotNull($shelf, 'shelf '.ShelfFixture::SHELF_NAME.' not found');

        self::assertTrue($this->getKoboDevice()->getShelves()->contains($shelf), 'Shelf should be associated with Kobo');

        $client?->request('DELETE', '/kobo/'.KoboFixture::ACCESS_KEY.'/v1/library/tags/'.$shelf->getUuid());

        self::assertResponseIsSuccessful();
        self::assertFalse($this->getKoboDevice()->getShelves()->contains($shelf), 'Shelf should NOT be associated with Kobo');

        // Re-add the shelf to the Kobo
        $shelf->addKoboDevice($this->getKoboDevice());
        $this->getEntityManager()->flush();
    }

    protected function getShelfByName(string $name): ?Shelf
    {
        return $this->getEntityManager()->getRepository(Shelf::class)->findOneBy(['name' => $name]);
    }
}
