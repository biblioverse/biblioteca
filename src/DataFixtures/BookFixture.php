<?php

namespace App\DataFixtures;

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

/**
 * @codeCoverageIgnore
 * @phpstan-type BookData array{
 *     title: string,
 *     uuid?: string,
 *     authors: list<string>,
 *     publishDate: string,
 *     language: string,
 *     publisher: string,
 *     extension: string,
 *     imageExtension: string,
 *     imageFilename: string,
 *     bookFilename: string,
 *     pageNumber: int
 * }
 */
class BookFixture extends Fixture implements DependentFixtureInterface
{
    public const BOOK_REFERENCE = 'book-the_odyssey';

    public const BOOK_ODYSSEY_FILENAME = 'real-TheOdysses.epub';
    public const BOOK_PAGE_REFERENCE = '7557680347007504212_1727-h-21.htm.xhtml';
    public const ID = 1;
    public const UUID = '54c8fb05-cf05-4cb6-9482-bc25fa49fa80';
    public const UUID_JUNGLE_BOOK = '6b6a0c48-d0e6-4722-8b43-d50e612dc240';

    public const NUMBER_OF_OWNED_YAML_BOOKS = 20;
    public const NUMBER_OF_UNOWNED_YAML_BOOKS = 1;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $yamlFile = __DIR__.'/books.yaml';

        /** @var array{books: list<BookData>} $data */
        $data = Yaml::parseFile($yamlFile);

        /** @var list<BookData> $books */
        $books = $data['books'];

        foreach ($books as $index => $bookData) {
            $book = new Book();
            if (array_key_exists('uuid', $bookData)) {
                $book->setUuid($bookData['uuid']);
            }
            $book->setTitle($bookData['title']);
            $book->setAuthors($bookData['authors']);
            $book->setPublishDate(new \DateTimeImmutable($bookData['publishDate']));
            $book->setLanguage($bookData['language']);
            $book->setPublisher($bookData['publisher']);
            $book->setExtension($bookData['extension']);
            $book->setImageExtension($bookData['imageExtension']);
            $book->setImageFilename($bookData['imageFilename']);
            $book->setImagePath('');
            $book->setBookFilename($bookData['bookFilename']);
            $book->setChecksum(md5($book->getBookFilename()));
            $book->setBookPath('');
            $book->setPageNumber($bookData['pageNumber']);

            $manager->persist($book);

            $reference = strtolower((string) preg_replace('/[^A-Za-z0-9-_]/', '_', $bookData['title']));

            $this->addReference('book-'.$reference, $book);
            $this->addReference('book-'.$index, $book);
        }

        $manager->flush();
    }
}
