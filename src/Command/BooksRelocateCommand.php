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

#[AsCommand(
    name: 'books:relocate',
    description: 'Add a short description for your command',
)]
class BooksRelocateCommand extends Command
{
    private BookRepository $bookRepository;
    private EntityManagerInterface $entityManager;
    private BookFileSystemManager $fileSystemManager;

    public function __construct(BookFileSystemManager $fileSystemManager, BookRepository $bookRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->bookRepository = $bookRepository;
        $this->entityManager = $entityManager;
        $this->fileSystemManager = $fileSystemManager;
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Execute the relocation. By default only a dry run is done');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $allBooks = $this->bookRepository->findAll();

        $executeChanges = (bool) $input->getOption('force');

        if (!$executeChanges) {
            $io->warning('This is a dry run. Use --force to execute the relocation');
        }

        $progressBar = new ProgressBar($output, count($allBooks));
        $progressBar->start();
        $relocationCounter = 0;
        foreach ($allBooks as $book) {
            $progressBar->advance();
            try {
                $calculatedPath = $this->fileSystemManager->getCalculatedFilePath($book, false).$this->fileSystemManager->getCalculatedFileName($book);
                $needsRelocation = $this->fileSystemManager->getCalculatedFilePath($book, false) !== $book->getBookPath();

                if ($needsRelocation) {
                    $relocationCounter++;

                    $io->writeln('Book '.$book->getTitle().': '.$book->getBookPath().'=>'.$calculatedPath);
                }
                if ($executeChanges) {
                    $book = $this->fileSystemManager->renameFiles($book);
                }
                $this->entityManager->persist($book);
            } catch (\Exception $e) {
                $io->error($e->getMessage());
                continue;
            }
        }
        $io->success('Relocated '.$relocationCounter.' books');
        if ($executeChanges) {
            $this->entityManager->flush();
        }
        $progressBar->finish();

        if ($executeChanges) {
            $io->writeln('');
            $io->writeln('Remove empty folders');
            $this->fileSystemManager->removeEmptySubFolders($this->fileSystemManager->getBooksDirectory());
            $this->fileSystemManager->removeEmptySubFolders($this->fileSystemManager->getCoverDirectory());
        }

        return Command::SUCCESS;
    }
}
