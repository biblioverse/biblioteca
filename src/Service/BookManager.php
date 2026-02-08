<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\RemoteBook;
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

/**
 * @phpstan-type MetadataType array{ title:string, authors: BookAuthor[], main_author: ?BookAuthor, description: ?string, publisher: ?string, publish_date: ?\DateTime, language: ?string, tags: string[], serie:?string, serie_index: ?float, cover: ?EbookCover }
 */
class BookManager
{
    public function __construct(private readonly BookFileSystemManagerInterface $fileSystemManager, private readonly EntityManagerInterface $entityManager, private readonly BookRepository $bookRepository)
    {
    }

    private function getChecksum(\SplFileInfo $file): string
    {
        $checkSum = shell_exec('sha1sum -b '.escapeshellarg($file->getRealPath()));
        if (!is_string($checkSum)) {
            throw new \RuntimeException('Could not calculate file Checksum:'.$file->getRealPath());
        }
        $checkSum = explode(' ', $checkSum);

        return $checkSum[0];
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
        $book->setChecksum($this->getChecksum($file));
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
        $this->upload($file, $book);
        $this->fileSystemManager->renameFiles($book);

        return $book;
    }

    public function updateBookLocation(Book $book, RemoteBook $remote): Book
    {
        $path = $this->fileSystemManager->getFolderName($remote, true);
        $filename = $this->fileSystemManager->getFileName($remote);

        if ($filename !== $book->getBookFilename()) {
            $book->setBookFilename($filename);
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
        $book->setChecksum($this->getChecksum($file));
        $book->addAuthor('unknown');

        $book->setExtension($file->getExtension());

        $this->upload($file, $book);
        $this->fileSystemManager->renameFiles($book);

        return $book;
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

    /**
     * @param \SplFileInfo[] $files
     * @throws \Exception
     */
    public function consumeBooks(array $files, ?InputInterface $input = null, ?OutputInterface $output = null): void
    {
        if (!$output instanceof OutputInterface) {
            $output = new NullOutput();
        }
        if (!$input instanceof InputInterface) {
            $input = new StringInput('');
        }
        $io = new SymfonyStyle($input, $output);
        if ($files === []) {
            $output->writeln('No files to process');

            return;
        }

        $progressBar = new ProgressBar($output, count($files));

        $progressBar->setFormat('debug');
        $progressBar->start();
        foreach ($files as $file) {
            try {
                $progressBar->setMessage(sprintf('File: %s', $file->getFilename()));
                $progressBar->display();
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
            } finally {
                $progressBar->advance();
            }
            $book = null;
            gc_collect_cycles();
        }
        $progressBar->finish();
    }

    public function consumeBook(\SplFileInfo $file): Book
    {
        $relative_path = str_replace($this->fileSystemManager->getLocalConsumeDirectory(), '', $file->getRealPath());
        $dir = pathinfo($relative_path, PATHINFO_DIRNAME);
        if ($dir === '.') {
            $dir = '';
        }
        $filename = str_replace($dir, '', $relative_path);
        $filename = ltrim($filename, '/');
        $book = $this->bookRepository->findOneBy(
            [
                'bookPath' => $dir,
                'bookFilename' => $filename,
            ]
        );

        if (null !== $book) {
            unlink($file->getRealPath());

            return $book;
        }

        $checksum = $this->getChecksum($file);
        $book = $this->bookRepository->findOneBy(['checksum' => $checksum]);

        if (null === $book) {
            try {
                $book = $this->createBook($file);
            } catch (BookExtractionException) {
                $book = $this->createBookWithoutMetadata($file);
            }
            unlink($file->getRealPath());

            return $book;
        }

        // Override current book file
        if ($book->getChecksum() !== $checksum) {
            $this->fileSystemManager->renameFiles($book);
            $book->setChecksum($checksum);
            $this->fileSystemManager->uploadFile($file, $this->fileSystemManager->getBookFile($book)->path);
        }

        unlink($file->getRealPath());

        return $book;
    }

    public function save(Book $book): void
    {
        $this->entityManager->persist($book);
        $this->entityManager->flush();
    }

    private function upload(\SplFileInfo $file, Book $book): Book
    {
        $location = $this->fileSystemManager->getCalculatedFilePath($book, false).$this->fileSystemManager->getCalculatedFileName($book);
        $remote = $this->fileSystemManager->uploadFile($file, $location);
        $book->setBookPath($this->fileSystemManager->getFolderName($remote, true));
        $book->setBookFilename($this->fileSystemManager->getFileName($remote));

        return $book;
    }
}
