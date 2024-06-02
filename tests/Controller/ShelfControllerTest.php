<?php

namespace App\Test\Controller;

use App\Entity\Shelf;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ShelfControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/shelf/crud/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Shelf::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Shelf index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'shelf[name]' => 'Testing',
            'shelf[slug]' => 'Testing',
            'shelf[queryString]' => 'Testing',
            'shelf[user]' => 'Testing',
            'shelf[books]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->repository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Shelf();
        $fixture->setName('My Title');
        $fixture->setSlug('My Title');
        $fixture->setQueryString('My Title');
        $fixture->setUser('My Title');
        $fixture->setBooks('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Shelf');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Shelf();
        $fixture->setName('Value');
        $fixture->setSlug('Value');
        $fixture->setQueryString('Value');
        $fixture->setUser('Value');
        $fixture->setBooks('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'shelf[name]' => 'Something New',
            'shelf[slug]' => 'Something New',
            'shelf[queryString]' => 'Something New',
            'shelf[user]' => 'Something New',
            'shelf[books]' => 'Something New',
        ]);

        self::assertResponseRedirects('/shelf/crud/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getName());
        self::assertSame('Something New', $fixture[0]->getSlug());
        self::assertSame('Something New', $fixture[0]->getQueryString());
        self::assertSame('Something New', $fixture[0]->getUser());
        self::assertSame('Something New', $fixture[0]->getBooks());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Shelf();
        $fixture->setName('Value');
        $fixture->setSlug('Value');
        $fixture->setQueryString('Value');
        $fixture->setUser('Value');
        $fixture->setBooks('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/shelf/crud/');
        self::assertSame(0, $this->repository->count([]));
    }
}
