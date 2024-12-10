<?php

namespace App\Tests;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GedmoTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testTimestamp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $book = new Book();
        $book->setTitle('test');
        $book->setChecksum(md5('test'));
        $book->setBookPath('test');
        $book->setBookFilename('test');
        $book->setExtension('ebook');

        $entityManager->persist($book);
        $entityManager->flush();

        self::assertNotNull($book->getCreated(), 'The created date should be set by Doctrine Extensions');
        self::assertNotNull($book->getUpdated(), 'The updated date should be set by Doctrine Extensions');

        $entityManager->remove($book);
        $entityManager->flush();
    }
}
