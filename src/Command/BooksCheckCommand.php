<?php

namespace App\Command;

use App\Repository\BookRepository;
use App\Service\BookFileSystemManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(
    name: 'books:check',
    description: 'Scan the books directory and add/Update books to the database',
)]
class BooksCheckCommand extends Command
{
    public function __construct(private BookFileSystemManager $fileSystemManager, private BookRepository $bookRepository, private RouterInterface $router)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln('Checking Books');

        $books = $this->bookRepository->findAll();
        $progressBar = new ProgressBar($output, count($books));
        $progressBar->setFormat('very_verbose');
        $progressBar->start();
        foreach ($books as $book) {
            $progressBar->advance();

            try {
                $file = $this->fileSystemManager->getBookFile($book);
            } catch (\Exception $e) {
                $io->writeln('');
                $io->writeln('Book not found: '.$book->getBookPath().'/'.$book->getBookFilename());
                $io->writeln('Book not found: '.$this->router->generate('app_book', ['book' => $book->getId(), 'slug' => $book->getSlug()]));
                continue;
            }
        }
        $io->writeln('');
        $progressBar->finish();

        $io->success('Done!');

        return Command::SUCCESS;
    }
}
