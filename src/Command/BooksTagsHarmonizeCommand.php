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
    private const int BATCH_SIZE = 15;

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
            ->addOption('exclude', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of book IDs to exclude from changes')
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
        /** @var string|null $excludeOption */
        $excludeOption = $input->getOption('exclude');
        $excludedIds = $excludeOption !== null
            ? array_map(intval(...), explode(',', $excludeOption))
            : [];

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

        /** @var array<int, array{genres: string[], tags: string[]}> $bookResults book ID => genres + tags */
        $bookResults = [];
        $progressBar = $io->createProgressBar(count($batches));
        $progressBar->start();

        foreach ($batches as $batch) {
            $result = $this->analyzeBookBatch($communicator, $batch, $language, $allowedGenres);
            foreach ($result as $bookId => $data) {
                $bookResults[$bookId] = $data;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Show proposed changes
        $io->section('Proposed changes:');
        $rows = [];
        foreach ($booksWithTags as $book) {
            $bookId = $book->getId();
            if (isset($bookResults[$bookId])) {
                $oldTags = implode(', ', $book->getTags() ?? []);
                $newGenres = implode(', ', $bookResults[$bookId]['genres']);
                $newTags = implode(', ', $bookResults[$bookId]['tags']);
                $combined = array_unique(array_merge($bookResults[$bookId]['genres'], $bookResults[$bookId]['tags']));
                $newAll = implode(', ', $combined);
                if ($oldTags !== $newAll) {
                    $excluded = in_array($bookId, $excludedIds, true);
                    $rows[] = [
                        $bookId,
                        mb_substr($book->getTitle(), 0, 30),
                        mb_substr($oldTags, 0, 30),
                        $newGenres,
                        mb_substr($newTags, 0, 40),
                        $excluded ? 'SKIP' : '',
                    ];
                }
            }
        }

        if ($rows === []) {
            $io->success('All books already have appropriate tags.');

            return Command::SUCCESS;
        }

        $io->table(['ID', 'Book', 'Current Tags', 'Genres', 'Tags', ''], $rows);

        // Show genre distribution
        $genreCounts = [];
        foreach ($bookResults as $data) {
            foreach ($data['genres'] as $genre) {
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
        $io->section('Applying changes...');
        $updatedCount = 0;

        foreach ($booksWithTags as $book) {
            $bookId = $book->getId();
            if (!isset($bookResults[$bookId]) || in_array($bookId, $excludedIds, true)) {
                continue;
            }

            $aiGenres = $bookResults[$bookId]['genres'];
            $aiTags = $bookResults[$bookId]['tags'];
            $newAll = array_unique(array_merge($aiGenres, $aiTags));
            $currentTags = $book->getTags() ?? [];

            if ($newAll === []) {
                continue;
            }

            // In replace mode, if the AI found genres but returned no curated tags,
            // preserve existing tags rather than wiping them.
            if ($mode === 'replace' && $aiTags === []) {
                $finalTags = array_unique(array_merge($aiGenres, $currentTags));
            } elseif ($mode === 'add') {
                $finalTags = array_unique(array_merge($currentTags, $newAll));
            } else {
                $finalTags = $newAll;
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
     * @return array<int, array{genres: string[], tags: string[]}> book ID => genres + tags
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

TASK: For each book, assign 1-2 main genres from the allowed list AND curate its existing tags.

ALLOWED GENRES (you MUST only use these exact values in {$languageName}):
{$allowedGenresJson}

BOOKS TO ANALYZE:
{$booksJson}

For each book:
1. Pick 1-2 main genres from the ALLOWED GENRES list above
2. Review the book's existing "tags" and KEEP any that are meaningful and specific (themes, settings, mood, character types, etc.)
3. REMOVE junk or overly generic tags like "Book", "Ebook", "General", "Fiction", "Romans", "Novela", "General Fiction", format descriptors, or language learning labels
4. You may add 1-2 new descriptive tags if they clearly apply to the book
5. Normalize tag casing to Title Case
6. Tags should be in {$languageName}

Return a JSON object where:
- Keys are the book IDs (as strings)
- Values are objects with:
  - "genres": array of 1-2 genre strings from the allowed list
  - "tags": array of curated tags (kept from existing + optionally added)

Example: {"123": {"genres": ["Science Fiction"], "tags": ["Space Exploration", "Artificial Intelligence"]}, "456": {"genres": ["Crime Fiction", "Thriller"], "tags": ["Murder Mystery", "Victorian England"]}}

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

            // Convert string keys to int and validate
            $validated = [];
            foreach ($mapping as $bookId => $data) {
                if (!is_array($data)) {
                    continue;
                }

                $genres = $data['genres'] ?? [];
                $tags = $data['tags'] ?? [];

                if (!is_array($genres)) {
                    $genres = [];
                }
                if (!is_array($tags)) {
                    $tags = [];
                }

                // Filter to only allowed genres
                $validGenres = array_values(array_filter($genres, fn ($g) => is_string($g) && in_array($g, $allowedGenres, true)));
                $validTags = array_values(array_filter($tags, fn ($t) => is_string($t) && $t !== ''));

                if ($validGenres !== []) {
                    $validated[(int) $bookId] = [
                        'genres' => $validGenres,
                        'tags' => $validTags,
                    ];
                }
            }

            return $validated;
        } catch (\JsonException) {
            return [];
        }
    }
}
