<?php

namespace App\Command;

use App\Ai\Communicator\AiAction;
use App\Ai\Communicator\AiCommunicatorInterface;
use App\Ai\Communicator\CommunicatorDefiner;
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
    name: 'books:series-harmonize',
    description: 'Use AI to detect and harmonize book series and clean up titles',
)]
class BooksSeriesHarmonizeCommand extends Command
{
    private const BATCH_SIZE = 20;

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
            ->addOption('apply', 'a', InputOption::VALUE_NONE, 'Apply the changes (without this flag, only shows the proposed changes)')
            ->addOption('language', 'l', InputOption::VALUE_REQUIRED, 'Language for series names (e.g., en, fr)', 'en')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = $input->getOption('apply') === true;
        /** @var string $language */
        $language = $input->getOption('language');

        $communicator = $this->aiCommunicator->getCommunicator(AiAction::Assistant);

        if (!$communicator instanceof AiCommunicatorInterface) {
            $io->error('AI communicator not available. Please configure an AI model in settings.');

            return Command::FAILURE;
        }

        $io->title('Series & Title Harmonization with AI ('.$communicator::class.')');

        // Get all books
        $books = $this->bookRepository->findAll();

        if ($books === []) {
            $io->success('No books found.');

            return Command::SUCCESS;
        }

        $io->note(sprintf('Found %d books to analyze.', count($books)));

        // Group books by author for better series detection
        $books = $this->groupBooksByAuthor($books);

        // Process in batches
        $batches = array_chunk($books, self::BATCH_SIZE);
        $io->note(sprintf('Processing in %d batches of up to %d books each.', count($batches), self::BATCH_SIZE));

        /** @var array<int, array{title: string|null, serie: string|null, serieIndex: float|null}> $bookData */
        $bookData = [];
        $progressBar = $io->createProgressBar(count($batches));
        $progressBar->start();

        foreach ($batches as $batch) {
            $result = $this->analyzeBookBatch($communicator, $batch, $language);
            foreach ($result as $bookId => $info) {
                $bookData[$bookId] = $info;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Show proposed changes
        $io->section('Proposed changes:');
        $rows = [];
        $changesCount = 0;

        foreach ($books as $book) {
            $bookId = $book->getId();
            if (!isset($bookData[$bookId])) {
                continue;
            }

            $newTitle = $bookData[$bookId]['title'];
            $newSerie = $bookData[$bookId]['serie'];
            $newIndex = $bookData[$bookId]['serieIndex'];
            $currentTitle = $book->getTitle();
            $currentSerie = $book->getSerie();
            $currentIndex = $book->getSerieIndex();

            // Check if there's a change
            $titleChanged = $newTitle !== null && $newTitle !== $currentTitle;
            $serieChanged = $newSerie !== null && $newSerie !== $currentSerie;
            $indexChanged = $newIndex !== null && $newIndex !== $currentIndex;

            if ($titleChanged || $serieChanged || $indexChanged) {
                $rows[] = [
                    mb_substr($currentTitle, 0, 30),
                    $titleChanged ? mb_substr($newTitle, 0, 30) : '-',
                    $newSerie ?? $currentSerie ?? '-',
                    $newIndex ?? $currentIndex ?? '-',
                ];
                $changesCount++;
            }
        }

        if ($rows === []) {
            $io->success('All books already have correct information.');

            return Command::SUCCESS;
        }

        $io->table(
            ['Current Title', 'New Title', 'Series', '#'],
            array_slice($rows, 0, 50),
        );
        if (count($rows) > 50) {
            $io->comment('... and '.(count($rows) - 50).' more books');
        }

        // Show series distribution
        $seriesCounts = [];
        foreach ($bookData as $info) {
            if ($info['serie'] !== null) {
                $seriesCounts[$info['serie']] = ($seriesCounts[$info['serie']] ?? 0) + 1;
            }
        }
        arsort($seriesCounts);

        if ($seriesCounts !== []) {
            $io->section('Series distribution (detected):');
            $seriesRows = [];
            foreach (array_slice($seriesCounts, 0, 30, true) as $serie => $count) {
                $seriesRows[] = [$serie, $count];
            }
            $io->table(['Series', 'Books'], $seriesRows);
        }

        if (!$apply) {
            $io->note(sprintf('%d books will be updated. Run with --apply to apply these changes.', $changesCount));

            return Command::SUCCESS;
        }

        // Apply changes
        $io->section('Applying changes...');
        $updatedCount = 0;

        foreach ($books as $book) {
            $bookId = $book->getId();
            if (!isset($bookData[$bookId])) {
                continue;
            }

            $newTitle = $bookData[$bookId]['title'];
            $newSerie = $bookData[$bookId]['serie'];
            $newIndex = $bookData[$bookId]['serieIndex'];
            $modified = false;

            if ($newTitle !== null && $newTitle !== $book->getTitle()) {
                $book->setTitle($newTitle);
                $modified = true;
            }

            if ($newSerie !== null && $newSerie !== $book->getSerie()) {
                $book->setSerie($newSerie);
                $modified = true;
            }

            if ($newIndex !== null && $newIndex !== $book->getSerieIndex()) {
                $book->setSerieIndex($newIndex);
                $modified = true;
            }

            if ($modified) {
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
        // Group by primary author
        $byAuthor = [];
        foreach ($books as $book) {
            $authors = $book->getAuthors();
            $primaryAuthor = $authors[0] ?? '_unknown_';
            $byAuthor[$primaryAuthor][] = $book;
        }

        // Sort authors by number of books (descending) for better batching
        uasort($byAuthor, fn ($a, $b) => count($b) <=> count($a));

        // Flatten back to array
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
     * @return array<int, array{title: string|null, serie: string|null, serieIndex: float|null}>
     */
    private function analyzeBookBatch(
        AiCommunicatorInterface $communicator,
        array $books,
        string $language,
    ): array {
        $booksData = [];
        foreach ($books as $book) {
            $booksData[] = [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'authors' => implode(', ', $book->getAuthors()),
                'currentSerie' => $book->getSerie(),
                'currentIndex' => $book->getSerieIndex(),
            ];
        }

        $booksJson = json_encode($booksData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You are an expert librarian specializing in book cataloging and series identification.

TASK: For each book, clean up the title and determine series information.

BOOKS TO ANALYZE:
{$booksJson}

For each book:
1. CLEAN THE TITLE: Remove series numbers, volume indicators, redundant series names from the title
   - "Dune 2 Dune Messiah" → "Dune Messiah"
   - "Foundation 1" → "Foundation"
   - "Harry Potter 1 - Philosopher's Stone" → "Harry Potter and the Philosopher's Stone"
2. DETECT SERIES: Identify if the book belongs to a series
3. DETECT INDEX: Find the book's position in the series
4. Keep titles and series names in their ORIGINAL language (do NOT translate)

IMPORTANT:
- Only return title if it needs cleaning (different from current)
- Keep series names in original language
- If standalone book, set serie and serieIndex to null
- Only include confident changes

Return a JSON object where:
- Keys are the book IDs (as strings)
- Values are objects with:
  - "title": cleaned title (string) or null if no change needed
  - "serie": series name (string) or null
  - "serieIndex": position in series (number) or null

Example: {
  "123": {"title": "Dune Messiah", "serie": "Dune", "serieIndex": 2},
  "456": {"title": null, "serie": null, "serieIndex": null},
  "789": {"title": null, "serie": "Foundation", "serieIndex": 3}
}

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

            // Validate and convert
            $validated = [];
            foreach ($mapping as $bookId => $info) {
                if (!is_array($info)) {
                    continue;
                }

                $title = $info['title'] ?? null;
                $serie = $info['serie'] ?? null;
                $serieIndex = $info['serieIndex'] ?? null;

                // Validate types
                if ($title !== null && !is_string($title)) {
                    $title = null;
                }
                if ($serie !== null && !is_string($serie)) {
                    $serie = null;
                }
                if ($serieIndex !== null && !is_numeric($serieIndex)) {
                    $serieIndex = null;
                }

                $validated[(int) $bookId] = [
                    'title' => $title,
                    'serie' => $serie,
                    'serieIndex' => $serieIndex !== null ? (float) $serieIndex : null,
                ];
            }

            return $validated;
        } catch (\JsonException) {
            return [];
        }
    }
}
