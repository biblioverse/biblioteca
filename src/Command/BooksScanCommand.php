<?php

namespace App\Command;

use App\Service\BookFileSystemManager;
use App\Service\BookManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'books:scan',
    description: 'Scan the books directory and add/Update books to the database',
)]
class BooksScanCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, private BookManager $bookManager, private BookFileSystemManager $fileSystemManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('book-path', 'b', InputOption::VALUE_REQUIRED, 'Which filepath to consume')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln('Scanning books directory');

        if ($input->getOption('book-path') !== null) {
            $path = $input->getOption('book-path');
            if (!is_string($path)) {
                throw new \Exception('Invalid path');
            }
            $info = new \SplFileInfo($path);
            $book = $this->bookManager->consumeBook($info);
            $this->entityManager->persist($book);
            $this->entityManager->flush();
            $this->fileSystemManager->renameFiles($book);
            $this->entityManager->flush();
        } else {
            $files = $this->fileSystemManager->getAllBooksFiles();
            $this->bookManager->consumeBooks(iterator_to_array($files), $input, $output);
        }
        $io->success('Done!');

        return Command::SUCCESS;
    }
}
