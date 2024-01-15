<?php

namespace App\Command;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\BookSuggestions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
            ->addOption('allow-google', 'g', InputOption::VALUE_NONE, 'Force the extraction of the cover')
            ->addArgument('book-id', InputOption::VALUE_REQUIRED, 'Which book to extract the cover from, use "all" for all books')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $bookId = $input->getArgument('book-id');
        $allowGoogle = $input->getOption('allow-google');

        if ($bookId === 'all') {
            $books = $this->bookRepository->findBy([], ['updated' => 'ASC']);
        } else {
            $books = $this->bookRepository->findBy(['id' => $bookId]);
        }

        $untaggedBooks = [];
        foreach ($books as $book) {
            /* @var Book $book */
            if ($book->getTags() === null || count($book->getTags()) === 0) {
                $untaggedBooks[] = $book;
            }
        }

        shuffle($untaggedBooks);

        if (count($books) === 0) {
            $io->error('No books found');

            return Command::FAILURE;
        }

        $io->note(sprintf('Processing: %s book(s)', count($untaggedBooks)));

        foreach ($untaggedBooks as $book) {
            /* @var Book $book */

            if ($book->getTags() !== null && count($book->getTags()) > 0) {
                $io->writeln('skipping '.$book->getTitle());
                continue;
            }

            $io->writeln('Querying for '.$book->getTitle().($book->getSerie() !== null ? ' ('.$book->getSerie().')' : ''));

            $suggestions = $this->bookSuggestions->getCategorySuggestions($book);
            if (count($suggestions['tags']) === 0 && $allowGoogle === true) {
                $io->writeln(' - Could not find results, trying google');
                $suggestions = $this->bookSuggestions->getGoogleSuggestions($book);
            }
            if (count($suggestions['tags']) > 0) {
                $io->writeln(' ✓ tags found');
                $book->setTags(array_values($suggestions['tags']));
            }

            $summary = count($suggestions['summary']) > 0 ? current($suggestions['summary']) : '';
            if ($summary !== '' && ($book->getSummary() === null || $book->getSummary() === '')) {
                $io->writeln(' ✓ summary found');
                $book->setSummary($summary);
            }

            if ($book->getTags() === []) {
                $book->addTag('No data on OpenLibrary');
                if ($allowGoogle === true) {
                    $book->addTag('No data on Google');
                }
            }

            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }
}