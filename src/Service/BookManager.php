<?php

namespace App\Service;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Kiwilan\Ebook\Ebook;
use Kiwilan\Ebook\EbookCover;
use Kiwilan\Ebook\Models\BookAuthor;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @phpstan-type MetadataType array{ title:string, authors: BookAuthor[], main_author: ?BookAuthor, description: ?string, publisher: ?string, publish_date: ?\DateTime, language: ?string, tags: string[], serie:?string, serie_index: ?float, cover: ?EbookCover }
 */
class BookManager
{
    public function __construct(private readonly BookFileSystemManagerInterface $fileSystemManager, private readonly EntityManagerInterface $entityManager, private readonly BookRepository $bookRepository)
    {
    }

    /**
     * @throws \Exception
     */
    public function createBook(\SplFileInfo $file, SymfonyStyle $io): Book
    {
        $book = new Book();

        $extractedMetadata = $this->extractEbookMetadata($file, $io);
        if ('' === $extractedMetadata['title'] || '.pdf' === $extractedMetadata['title']) {
            $extractedMetadata['title'] = $file->getBasename();
        }
        $book->setTitle($extractedMetadata['title']);
        $book->setChecksum($this->fileSystemManager->getFileChecksum($file));
        if (null !== $extractedMetadata['main_author'] && null !== $extractedMetadata['main_author']->getName()) {
            $book->addAuthor($extractedMetadata['main_author']->getName());
        }

        foreach ($extractedMetadata['authors'] as $author) {
            if ($author->getName() !== null) {
                $book->addAuthor($author->getName());
            }
        }

        if ([] === $book->getAuthors()) {
            $book->addAuthor('unknown');
        }

        $book->setSummary($extractedMetadata['description']);
        if (null !== $extractedMetadata['serie']) {
            $book->setSerie($extractedMetadata['serie']);
            $book->setSerieIndex($extractedMetadata['serie_index']);
        }
        $book->setPublisher($extractedMetadata['publisher']);
        if (2 === strlen($extractedMetadata['language'] ?? '')) {
            $book->setLanguage($extractedMetadata['language']);
        }

        $book->setExtension($file->getExtension());
        $book->setTags($extractedMetadata['tags']);

        $book->setBookPath('');
        $book->setBookFilename('');

        return $this->updateBookLocation($book, $file);
    }

    public function updateBookLocation(Book $book, \SplFileInfo $file): Book
    {
        $path = $this->fileSystemManager->getFolderName($file);
        if ($path !== $book->getBookPath()) {
            $book->setBookPath($path);
        }
        if ($file->getFilename() !== $book->getBookFilename()) {
            $book->setBookFilename($file->getFilename());
        }

        return $book;
    }

    /**
     * @return MetadataType
     *
     * @throws \Exception
     */
    public function extractEbookMetadata(\SplFileInfo $file, SymfonyStyle $io): array
    {
        try {
            if (!Ebook::isValid($file->getRealPath())) {
                throw new \RuntimeException('Invalid eBook: "' . $file->getRealPath() . '"');
            }

            $ebook = Ebook::read($file->getRealPath());
            if (!$ebook instanceof Ebook) {
                throw new \RuntimeException('Could not read eBook: "' . $file->getRealPath() . '"');
            }
        } catch (\Throwable $e) {

            $io->error('Could not read eBook' . $file->getRealPath() . "\n" . $e->getMessage() . "\n" . $e->getTraceAsString());

            $ebook = null;

            return [
                'title' => $file->getFilename(),
                'authors' => [new BookAuthor('unknown')], // BookAuthor[] (`name`: string, `role`: string)
                'main_author' => new BookAuthor('unknown'), // ?BookAuthor => First BookAuthor (`name`: string, `role`: string)
                'description' => null, // ?string
                'publisher' => null, // ?string
                'publish_date' => null, // ?DateTime
                'language' => null, // ?string
                'tags' => [], // string[] => `subject` in EPUB, `keywords` in PDF, `genres` in CBA
                'serie' => null, // ?string => `calibre:series` in EPUB, `series` in CBA
                'serie_index' => null, // ?int => `calibre:series_index` in EPUB, `number` in CBA
                'cover' => null, //  ?EbookCover => cover of book
            ];
        }

        return [
            'title' => $ebook->getTitle() ?? $file->getBasename('.'.$file->getExtension()), // string
            'authors' => $ebook->getAuthors(), // BookAuthor[] (`name`: string, `role`: string)
            'main_author' => $ebook->getAuthorMain(), // ?BookAuthor => First BookAuthor (`name`: string, `role`: string)
            'description' => $ebook->getDescription(), // ?string
            'publisher' => $ebook->getPublisher(), // ?string
            'publish_date' => $ebook->getPublishDate(), // ?DateTime
            'language' => $ebook->getLanguage(), // ?string
            'tags' => $ebook->getTags(), // string[] => `subject` in EPUB, `keywords` in PDF, `genres` in CBA
            'serie' => $ebook->getSeries(), // ?string => `calibre:series` in EPUB, `series` in CBA
            'serie_index' => $ebook->getVolume(), // ?int => `calibre:series_index` in EPUB, `number` in CBA
            'cover' => $ebook->getCover(), //  ?EbookCover => cover of book
        ];
    }

    public function consumeBooks(array $files, ?InputInterface $input = null, ?OutputInterface $output = null): void
    {
        if (!$output instanceof OutputInterface) {
            $output = new NullOutput();
        }
        if (!$input instanceof InputInterface) {
            $input = new StringInput('');
        }
        $io = new SymfonyStyle($input, $output);

        $progressBar = new ProgressBar($output, count($files));

        $progressBar->setFormat('debug');
        $progressBar->start();
        foreach ($files as $file) {
            $progressBar->advance();
            try {
                $book = $this->consumeBook($file, $io);
                $progressBar->setMessage($file->getFilename());

                $this->entityManager->persist($book);
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $io->error('died during process of '.$file->getRealPath());
                $io->error($e->getMessage());
                throw $e;
            }
            $book = null;
            gc_collect_cycles();
        }
        $progressBar->finish();
    }

    public function consumeBook(\SplFileInfo $file, SymfonyStyle $io): Book
    {
        $book = $this->bookRepository->findOneBy(
            [
                'bookPath' => $this->fileSystemManager->getFolderName($file),
                'bookFilename' => $file->getFilename(),
            ]
        );

        if (null !== $book) {
            return $book;
        }

        $checksum = $this->fileSystemManager->getFileChecksum($file);
        $book = $this->bookRepository->findOneBy(['checksum' => $checksum]);

        return null === $book ? $this->createBook($file, $io) : $this->updateBookLocation($book, $file);
    }
}
