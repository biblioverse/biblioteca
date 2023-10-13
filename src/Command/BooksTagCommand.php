<?php

namespace App\Command;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\BookSuggestions;
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
    name: 'books:tag',
    description: 'Add a short description for your command',
)]
class BooksTagCommand extends Command
{
    public function __construct(private BookSuggestions $bookSuggestions, private BookRepository $bookRepository, private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('book-id', InputOption::VALUE_REQUIRED, 'Which book to extract the cover from, use "all" for all books')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $bookId = $input->getArgument('book-id');
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

            if ($book->getTags() !== null && count($book->getTags()) > 0) {
                continue;
            }

            $suggestions = $this->bookSuggestions->getCategorySuggestions($book);
            if (count($suggestions['tags']) === 0) {
                $suggestions = $this->bookSuggestions->getGoogleSuggestions($book);
            }
            $book->setTags(array_values($suggestions['tags']));

            $summary = count($suggestions['summary']) > 0 ? current($suggestions['summary']) : '';
            if ($summary !== '' && ($book->getSummary() === null || $book->getSummary() === '')) {
                $book->setSummary($summary);
            }
            $this->entityManager->flush();

        }

        $progressBar->finish();

        return Command::SUCCESS;
    }
}
