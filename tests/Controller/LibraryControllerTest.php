<?php

namespace App\Test\Controller;

use App\Entity\Library;
use App\Repository\LibraryRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LibraryControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private LibraryRepository $repository;
    private string $path = '/library/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->repository = static::getContainer()->get('doctrine')->getRepository(Library::class);

        foreach ($this->repository->findAll() as $object) {
            $this->repository->remove($object, true);
        }
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Library index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $originalNumObjectsInRepository = count($this->repository->findAll());

        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'library[name]' => 'Testing',
            'library[schedule]' => 'Testing',
            'library[body]' => 'Testing',
            'library[image]' => 'Testing',
            'library[phone]' => 'Testing',
            'library[email]' => 'Testing',
            'library[address]' => 'Testing',
            'library[canOrderOnOrderPage]' => 'Testing',
        ]);

        self::assertResponseRedirects('/library/');

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Library();
        $fixture->setName('My Title');
        $fixture->setSchedule('My Title');
        $fixture->setBody('My Title');
        $fixture->setImage('My Title');
        $fixture->setPhone('My Title');
        $fixture->setEmail('My Title');
        $fixture->setAddress('My Title');
        $fixture->setCanOrderOnOrderPage('My Title');

        $this->repository->save($fixture, true);

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Library');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Library();
        $fixture->setName('My Title');
        $fixture->setSchedule('My Title');
        $fixture->setBody('My Title');
        $fixture->setImage('My Title');
        $fixture->setPhone('My Title');
        $fixture->setEmail('My Title');
        $fixture->setAddress('My Title');
        $fixture->setCanOrderOnOrderPage('My Title');

        $this->repository->save($fixture, true);

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'library[name]' => 'Something New',
            'library[schedule]' => 'Something New',
            'library[body]' => 'Something New',
            'library[image]' => 'Something New',
            'library[phone]' => 'Something New',
            'library[email]' => 'Something New',
            'library[address]' => 'Something New',
            'library[canOrderOnOrderPage]' => 'Something New',
        ]);

        self::assertResponseRedirects('/library/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getName());
        self::assertSame('Something New', $fixture[0]->getSchedule());
        self::assertSame('Something New', $fixture[0]->getBody());
        self::assertSame('Something New', $fixture[0]->getImage());
        self::assertSame('Something New', $fixture[0]->getPhone());
        self::assertSame('Something New', $fixture[0]->getEmail());
        self::assertSame('Something New', $fixture[0]->getAddress());
        self::assertSame('Something New', $fixture[0]->getCanOrderOnOrderPage());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();

        $originalNumObjectsInRepository = count($this->repository->findAll());

        $fixture = new Library();
        $fixture->setName('My Title');
        $fixture->setSchedule('My Title');
        $fixture->setBody('My Title');
        $fixture->setImage('My Title');
        $fixture->setPhone('My Title');
        $fixture->setEmail('My Title');
        $fixture->setAddress('My Title');
        $fixture->setCanOrderOnOrderPage('My Title');

        $this->repository->save($fixture, true);

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertSame($originalNumObjectsInRepository, count($this->repository->findAll()));
        self::assertResponseRedirects('/library/');
    }
}
