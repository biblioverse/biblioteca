<?php

namespace App\Command;

use App\Repository\BookRepository;
use App\Service\BookFileSystemManager;
use App\Service\BookManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'books:relocate',
    description: 'Add a short description for your command',
)]
class BooksRelocateCommand extends Command
{
    private BookRepository $bookRepository;
    private EntityManagerInterface $entityManager;
    private BookFileSystemManager $fileSystemManager;

    public function __construct(BookFileSystemManager$fileSystemManager, BookRepository $bookRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->bookRepository = $bookRepository;
        $this->entityManager = $entityManager;
        $this->fileSystemManager = $fileSystemManager;
    }

    protected function configure(): void
    {

    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $allBooks = $this->bookRepository->findAll();

        $progressBar = new ProgressBar($output, count($allBooks));
        $progressBar->start();
        foreach ($allBooks as $book) {
            $progressBar->advance();
            try {

                $book = $this->fileSystemManager->renameFiles($book);
                $this->entityManager->persist($book);

            }catch (\Exception $e) {
                $io->error($e->getMessage());
                continue;
            }
        }
        $this->entityManager->flush();

        $progressBar->finish();

        $io->writeln('');
        $io->writeln('Remove empty folders');
        $this->fileSystemManager->removeEmptySubFolders($this->fileSystemManager->getBooksDirectory());
        $this->fileSystemManager->removeEmptySubFolders($this->fileSystemManager->getCoverDirectory());

        return Command::SUCCESS;
    }
}
