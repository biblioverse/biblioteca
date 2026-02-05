<?php

namespace App\Command;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\BookFileSystemManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'books:cleanup',
    description: 'Find and remove orphaned books (database entries without corresponding files)',
)]
class BooksCleanupCommand extends Command
{
    public function __construct(
        private readonly BookFileSystemManagerInterface $fileSystemManager,
        private readonly BookRepository $bookRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Actually delete the orphaned books (without this flag, only lists them)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Alias for default behavior (list only, no deletion)')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $delete = $input->getOption('delete');

        $books = $this->bookRepository->findAll();

        if ($books === []) {
            $io->success('No books in database');

            return Command::SUCCESS;
        }

        $io->note(sprintf('Checking %d book(s) for orphaned entries...', count($books)));

        $progressBar = new ProgressBar($output, count($books));
        $progressBar->setFormat('debug');
        $progressBar->start();

        $orphanedBooks = [];

        foreach ($books as $book) {
            $progressBar->advance();

            if (!$this->fileSystemManager->fileExist($book)) {
                $orphanedBooks[] = $book;
            }
        }

        $progressBar->finish();
        $output->writeln('');

        if ($orphanedBooks === []) {
            $io->success('No orphaned books found. All database entries have corresponding files.');

            return Command::SUCCESS;
        }

        $io->warning(sprintf('Found %d orphaned book(s) (file not found on disk):', count($orphanedBooks)));

        $table = new Table($output);
        $table->setHeaders(['ID', 'Title', 'Extension', 'Path', 'Filename']);

        foreach ($orphanedBooks as $book) {
            $table->addRow([
                $book->getId(),
                mb_substr($book->getTitle() ?? '', 0, 40),
                $book->getExtension(),
                $book->getBookPath(),
                mb_substr($book->getBookFilename() ?? '', 0, 50),
            ]);
        }

        $table->render();

        if (!$delete) {
            $io->note('Run with --delete to remove these orphaned entries from the database.');

            return Command::SUCCESS;
        }

        $io->caution('Deleting orphaned books from database...');

        foreach ($orphanedBooks as $book) {
            $this->entityManager->remove($book);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Deleted %d orphaned book(s) from the database.', count($orphanedBooks)));

        return Command::SUCCESS;
    }
}
