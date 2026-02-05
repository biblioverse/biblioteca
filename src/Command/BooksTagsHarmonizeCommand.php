<?php

namespace App\Command;

use App\Ai\Communicator\AiAction;
use App\Ai\Communicator\AiCommunicatorInterface;
use App\Ai\Communicator\CommunicatorDefiner;
use App\Ai\GenreList;
use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'books:tags-harmonize',
    description: 'Use AI to harmonize and consolidate tags across all books',
)]
class BooksTagsHarmonizeCommand extends Command
{
    private const BATCH_SIZE = 15;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BookRepository $bookRepository,
        private readonly CommunicatorDefiner $aiCommunicator,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption('apply', 'a', InputOption::VALUE_NONE, 'Apply the harmonization (without this flag, only shows the proposed changes)')
            ->addOption('language', 'l', InputOption::VALUE_REQUIRED, 'Target language for tags (e.g., en, fr)', 'en')
            ->addOption('mode', 'm', InputOption::VALUE_REQUIRED, 'How to apply changes: "replace" (default) or "add" (keeps original tags)', 'replace')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = $input->getOption('apply') === true;
        /** @var string $language */
        $language = $input->getOption('language');
        /** @var string $mode */
        $mode = $input->getOption('mode');

        if (!in_array($mode, ['replace', 'add'], true)) {
            $io->error('Invalid mode. Use "replace" or "add".');

            return Command::FAILURE;
        }

        $communicator = $this->aiCommunicator->getCommunicator(AiAction::Assistant);

        if (!$communicator instanceof AiCommunicatorInterface) {
            $io->error('AI communicator not available. Please configure an AI model in settings.');

            return Command::FAILURE;
        }

        $io->title('Tag Harmonization with AI ('.$communicator::class.')');

        $allowedGenres = GenreList::getForLanguage($language);
        $io->note(sprintf('Using %d predefined genres for %s', count($allowedGenres), $language));

        if ($mode === 'add') {
            $io->note('Add mode: harmonized genres will be added alongside existing tags.');
        }

        // Get all books with tags
        $books = $this->bookRepository->findAll();
        $booksWithTags = [];

        foreach ($books as $book) {
            $tags = $book->getTags();
            if ($tags !== null && $tags !== []) {
                $booksWithTags[] = $book;
            }
        }

        if ($booksWithTags === []) {
            $io->success('No books with tags found.');

            return Command::SUCCESS;
        }

        $io->note(sprintf('Found %d books with tags to analyze.', count($booksWithTags)));

        // Group books by author for better genre consistency
        $booksWithTags = $this->groupBooksByAuthor($booksWithTags);

        // Process in batches
        $batches = array_chunk($booksWithTags, self::BATCH_SIZE);
        $io->note(sprintf('Processing in %d batches of up to %d books each.', count($batches), self::BATCH_SIZE));

        /** @var array<int, string[]> $bookGenres book ID => new genres */
        $bookGenres = [];
        $progressBar = $io->createProgressBar(count($batches));
        $progressBar->start();

        foreach ($batches as $batch) {
            $result = $this->analyzeBookBatch($communicator, $batch, $language, $allowedGenres);
            foreach ($result as $bookId => $genres) {
                $bookGenres[$bookId] = $genres;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Show proposed changes
        $io->section('Proposed genre assignments:');
        $rows = [];
        foreach ($booksWithTags as $book) {
            $bookId = $book->getId();
            if (isset($bookGenres[$bookId])) {
                $oldTags = implode(', ', $book->getTags() ?? []);
                $newGenres = implode(', ', $bookGenres[$bookId]);
                if ($oldTags !== $newGenres) {
                    $rows[] = [
                        mb_substr($book->getTitle(), 0, 40),
                        mb_substr($oldTags, 0, 40),
                        $newGenres,
                    ];
                }
            }
        }

        if ($rows === []) {
            $io->success('All books already have appropriate genres.');

            return Command::SUCCESS;
        }

        $io->table(['Book', 'Current Tags', 'New Genres'], array_slice($rows, 0, 50));
        if (count($rows) > 50) {
            $io->comment('... and '.(count($rows) - 50).' more books');
        }

        // Show genre distribution
        $genreCounts = [];
        foreach ($bookGenres as $genres) {
            foreach ($genres as $genre) {
                $genreCounts[$genre] = ($genreCounts[$genre] ?? 0) + 1;
            }
        }
        arsort($genreCounts);
        $io->section('Genre distribution:');
        $genreRows = [];
        foreach ($genreCounts as $genre => $count) {
            $genreRows[] = [$genre, $count];
        }
        $io->table(['Genre', 'Books'], $genreRows);

        if (!$apply) {
            $io->note('Run with --apply to apply these changes.');

            return Command::SUCCESS;
        }

        // Apply changes
        $io->section('Applying genre assignments...');
        $updatedCount = 0;

        foreach ($booksWithTags as $book) {
            $bookId = $book->getId();
            if (!isset($bookGenres[$bookId])) {
                continue;
            }

            $newGenres = $bookGenres[$bookId];
            $currentTags = $book->getTags() ?? [];

            if ($mode === 'add') {
                // Add new genres to existing tags
                $finalTags = array_unique(array_merge($currentTags, $newGenres));
            } else {
                // Replace with new genres only
                $finalTags = $newGenres;
            }

            if ($currentTags !== $finalTags) {
                $book->setTags(array_values($finalTags));
                $updatedCount++;
            }
        }

        $this->em->flush();

        $io->success(sprintf('Harmonization complete. Updated %d books.', $updatedCount));

        return Command::SUCCESS;
    }

    /**
     * Group books by primary author to keep same-author books in same batches.
     *
     * @param Book[] $books
     * @return Book[]
     */
    private function groupBooksByAuthor(array $books): array
    {
        $byAuthor = [];
        foreach ($books as $book) {
            $authors = $book->getAuthors();
            $primaryAuthor = $authors[0] ?? '_unknown_';
            $byAuthor[$primaryAuthor][] = $book;
        }

        // Sort authors by number of books (descending) for better batching
        uasort($byAuthor, fn ($a, $b) => count($b) <=> count($a));

        $result = [];
        foreach ($byAuthor as $authorBooks) {
            foreach ($authorBooks as $book) {
                $result[] = $book;
            }
        }

        return $result;
    }

    /**
     * @param Book[] $books
     * @param string[] $allowedGenres
     * @return array<int, string[]> book ID => genres
     */
    private function analyzeBookBatch(
        AiCommunicatorInterface $communicator,
        array $books,
        string $language,
        array $allowedGenres,
    ): array {
        $booksData = [];
        foreach ($books as $book) {
            $booksData[] = [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'authors' => implode(', ', $book->getAuthors()),
                'tags' => $book->getTags(),
            ];
        }

        $booksJson = json_encode($booksData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $allowedGenresJson = json_encode($allowedGenres, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $languageNames = [
            'fr' => 'French',
            'en' => 'English',
            'de' => 'German',
            'es' => 'Spanish',
        ];
        $languageName = $languageNames[$language] ?? $language;

        $prompt = <<<PROMPT
You are a librarian expert in book categorization.

TASK: For each book, analyze its title, authors, and current tags to determine the 1-2 most appropriate genres.

ALLOWED GENRES (you MUST only use these exact values in {$languageName}):
{$allowedGenresJson}

BOOKS TO ANALYZE:
{$booksJson}

For each book:
1. Look at the title, authors, and ALL current tags together
2. Determine what genre(s) best describe this book (1-2 genres max)
3. Choose ONLY from the allowed genres list

Return a JSON object where:
- Keys are the book IDs (as strings)
- Values are arrays of 1-2 genre strings from the allowed list

Example: {"123": ["Science-Fiction"], "456": ["Policier", "Thriller"]}

Return only the JSON, no other text.
PROMPT;

        $result = $communicator->interrogate($prompt);

        // Clean up result
        $result = trim($result, "´`\n\r\t\v\0 ");
        if (str_starts_with($result, 'json')) {
            $result = substr($result, 4);
        }
        $result = preg_replace('/<think>.*?<\/think>/s', '', $result) ?? '';

        try {
            $mapping = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($mapping)) {
                return [];
            }

            // Convert string keys to int and validate genres
            $validated = [];
            foreach ($mapping as $bookId => $genres) {
                if (!is_array($genres)) {
                    continue;
                }
                // Filter to only allowed genres
                $validGenres = array_filter($genres, fn ($g) => in_array($g, $allowedGenres, true));
                if ($validGenres !== []) {
                    $validated[(int) $bookId] = array_values($validGenres);
                }
            }

            return $validated;
        } catch (\JsonException) {
            return [];
        }
    }
}
