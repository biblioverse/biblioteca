<?php

namespace App\Command;

use App\Repository\BookRepository;
use App\Service\BookFileSystemManager;
use App\Service\BookManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'books:scan',
    description: 'Scan the books directory and add/Update books to the database',
)]
class BooksScanCommand extends Command
{
    private BookManager $bookManager;
    private BookRepository $bookRepository;
    private EntityManagerInterface $entityManager;
    private BookFileSystemManager $fileSystemManager;

    public function __construct(BookManager $bookManager, BookFileSystemManager $fileSystemManager, BookRepository $bookRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->bookManager = $bookManager;
        $this->bookRepository = $bookRepository;
        $this->entityManager = $entityManager;
        $this->fileSystemManager = $fileSystemManager;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln('Scanning books directory');

        $files = $this->fileSystemManager->getAllBooksFiles();
        $progressBar = new ProgressBar($output, iterator_count($files));
        $progressBar->setFormat('debug');
        $progressBar->start();
        foreach ($files as $file) {
            $progressBar->advance();
            try {
                $flush = false;
                $progressBar->setMessage($file->getFilename());
                $book = $this->bookRepository->findOneBy(
                    [
                        'bookPath' => $this->fileSystemManager->getFolderName($file),
                        'bookFilename' => $file->getFilename(),
                    ]);

                if (null !== $book) {
                    continue;
                }

                $checksum = $this->fileSystemManager->getFileChecksum($file);
                $book = $this->bookRepository->findOneBy(['checksum' => $checksum]);
                if (null === $book) {
                    $book = $this->bookManager->createBook($file);
                    $flush = true;
                } else {
                    $previousPath = $book->getBookPath();
                    $previousName = $book->getBookFilename();
                    $book = $this->bookManager->updateBookLocation($book, $file);
                    if ($book->getBookPath() !== $previousPath || $book->getBookFilename() !== $previousName) {
                        $flush = true;
                    }
                }

                if (true === $flush) {
                    $this->entityManager->persist($book);
                    $this->entityManager->flush();
                }
            } catch (\Exception $e) {
                $io->error('died during process of '.$file->getRealPath());
                $io->error($e->getMessage());
                throw $e;
            }
            $book = null;
            gc_collect_cycles();
        }
        $io->writeln('');
        $io->writeln('Persisting books...');
        $this->entityManager->flush();
        $progressBar->finish();
        $io->success('Done!');

        return Command::SUCCESS;
    }
}
