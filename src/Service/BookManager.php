<?php

namespace App\Service;

use App\Entity\Book;
use App\Exception\BookExtractionException;
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
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @phpstan-type MetadataType array{ title:string, authors: BookAuthor[], main_author: ?BookAuthor, description: ?string, publisher: ?string, publish_date: ?\DateTime, language: ?string, tags: string[], serie:?string, serie_index: ?float, cover: ?EbookCover }
 */
class BookManager
{
    public function __construct(private readonly BookFileSystemManagerInterface $fileSystemManager, private readonly EntityManagerInterface $entityManager, private readonly BookRepository $bookRepository, #[Autowire(param: 'ALLOW_BOOK_RELOCATION')] private readonly bool $allowBookRelocation)
    {
    }

    /**
     * @throws \Exception
     */
    public function createBook(\SplFileInfo $file): Book
    {
        $book = new Book();

        $extractedMetadata = $this->extractEbookMetadata($file);
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

        if ($file->getFilename() !== $book->getBookFilename()) {
            $book->setBookFilename($file->getFilename());
        }

        $consumePath = $this->fileSystemManager->getBooksDirectory().'consume';
        if (str_starts_with($file->getRealPath(), $consumePath) && $this->allowBookRelocation) {
            $book->setBookPath($path);
            $this->fileSystemManager->renameFiles($book);

            return $book;
        }

        if ($path !== $book->getBookPath()) {
            $book->setBookPath($path);
        }

        return $book;
    }

    public function createBookWithoutMetadata(\SplFileInfo $file): Book
    {
        $book = new Book();

        $book->setTitle($file->getBasename());
        $book->setChecksum($this->fileSystemManager->getFileChecksum($file));
        $book->addAuthor('unknown');

        $book->setExtension($file->getExtension());

        $book->setBookPath('');
        $book->setBookFilename('');

        return $this->updateBookLocation($book, $file);
    }

    /**
     * @return MetadataType
     *
     * @throws \Exception
     */
    public function extractEbookMetadata(\SplFileInfo $file): array
    {
        try {
            if (!Ebook::isValid($file->getRealPath())) {
                throw new BookExtractionException('Invalid eBook', $file->getRealPath());
            }

            $ebook = Ebook::read($file->getRealPath());
            if (!$ebook instanceof Ebook) {
                throw new BookExtractionException('Could not read eBook', $file->getRealPath());
            }
        } catch (\Throwable $e) {
            throw new BookExtractionException('Ebook Library threw an exception', $file->getRealPath(), $e);
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
                $progressBar->setMessage($file->getFilename());
                $book = null;
                try {
                    $book = $this->consumeBook($file);
                } catch (BookExtractionException $e) {
                    $book = $this->createBookWithoutMetadata($file);
                    $io->error($e->getMessage());
                    if ($e->getPrevious() instanceof \Exception) {
                        $io->error('Caused by '.$e->getPrevious()->getMessage());
                    }
                }

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

    public function consumeBook(\SplFileInfo $file): Book
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

        return null === $book ? $this->createBook($file) : $this->updateBookLocation($book, $file);
    }

    public function save(Book $book): void
    {
        $this->entityManager->persist($book);
        $this->entityManager->flush();
    }
}
