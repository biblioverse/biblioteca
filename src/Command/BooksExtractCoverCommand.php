<?php

namespace App\Command;

use App\Repository\BookRepository;
use App\Service\BookFileSystemManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'books:extract-cover',
    description: 'Add a short description for your command',
)]
class BooksExtractCoverCommand extends Command
{
    public function __construct(private BookFileSystemManager $fileSystemManager, private BookRepository $bookRepository, private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the extraction of the cover')
            ->addArgument('book-id', InputOption::VALUE_REQUIRED, 'Which book to extract the cover from, use "all" for all books')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $bookId = $input->getArgument('book-id');
        $force = $input->getOption('force');
        if ($bookId === 'all') {
            $books = $this->bookRepository->findAll();
        } else {
            $books = $this->bookRepository->findBy(['id' => $bookId]);
        }

        if (count($books) === 0) {
            $io->error('No books found');

            return Command::FAILURE;
        }

        $io->note(sprintf('Processing: %s book(s)', count($books)));

        $progressBar = new ProgressBar($output, count($books));
        $progressBar->start();
        $fs = new Filesystem();
        foreach ($books as $book) {
            /* @var Book $book */
            $progressBar->advance();

            if ($force === true || $book->getImageFilename() === null || !$fs->exists($this->fileSystemManager->getCoverDirectory().$book->getImagePath().$book->getImageFilename())) {
                try {
                    $book = $this->fileSystemManager->extractCover($book);
                    $this->entityManager->persist($book);
                    $this->entityManager->flush();
                } catch (\Exception $e) {
                    $io->error($e->getMessage());
                    continue;
                }
            }
        }
        $this->entityManager->flush();

        $progressBar->finish();

        return Command::SUCCESS;
    }
}
