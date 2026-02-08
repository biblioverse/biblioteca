<?php

namespace App\Command;

use App\Service\BookFileSystemManagerInterface;
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
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookManager $bookManager,
        private readonly BookFileSystemManagerInterface $fileSystemManager,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption('book-path', 'b', InputOption::VALUE_REQUIRED, 'Which file path to consume')
            ->addOption('consume', 'c', InputOption::VALUE_NONE, 'scan only the consume directory')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force re-import of metadata for existing books')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $force = $input->getOption('force') === true;

        if ($input->getOption('book-path') !== null) {
            $path = $input->getOption('book-path');
            if (!is_string($path)) {
                throw new \Exception('Invalid path');
            }
            $io->writeln('Consuming '.$path);
            $info = new \SplFileInfo($path);
            $book = $this->bookManager->consumeBook($info, $force);
            $this->entityManager->persist($book);
            $this->entityManager->flush();
            $this->fileSystemManager->renameFiles($book);
            $this->entityManager->flush();
        } else {
            $io->writeln('Scanning books directory'.($force ? ' (force metadata refresh)' : ''));
            $consume = $input->getOption('consume');
            if (!is_bool($consume)) {
                $consume = false;
            }
            $files = $this->fileSystemManager->getAllBooksFiles($consume);
            $this->bookManager->consumeBooks(iterator_to_array($files), $input, $output, $force);
        }
        $io->success('Done!');

        return Command::SUCCESS;
    }
}
