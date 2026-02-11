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
    private const int BATCH_SIZE = 20;

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
            ->addOption('exclude', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of book IDs to exclude from changes')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = $input->getOption('apply') === true;
        /** @var string|null $excludeOption */
        $excludeOption = $input->getOption('exclude');
        $excludedIds = $excludeOption !== null
            ? array_map('intval', explode(',', $excludeOption))
            : [];

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
            $result = $this->analyzeBookBatch($communicator, $batch);
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
                $excluded = in_array($bookId, $excludedIds, true);
                $rows[] = [
                    $bookId,
                    mb_substr($currentTitle, 0, 30),
                    $titleChanged ? mb_substr($newTitle, 0, 30) : '(no change)',
                    $newSerie ?? $currentSerie ?? '-',
                    $newIndex ?? $currentIndex ?? '-',
                    $book->getLanguage() ?? '-',
                    $excluded ? 'SKIP' : '',
                ];
                if (!$excluded) {
                    $changesCount++;
                }
            }
        }

        if ($rows === []) {
            $io->success('All books already have correct information.');

            return Command::SUCCESS;
        }

        // Sort by language, then series, then index
        usort($rows, function (array $a, array $b): int {
            $cmp = $a[5] <=> $b[5];
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = $a[3] <=> $b[3];
            if ($cmp !== 0) {
                return $cmp;
            }

            return ((float) $a[4]) <=> ((float) $b[4]);
        });

        $io->table(
            ['ID', 'Current Title', 'New Title', 'Series', '#', 'Lang', ''],
            $rows,
        );

        // Show series distribution grouped by language
        $seriesByLang = [];
        $booksById = [];
        foreach ($books as $book) {
            $booksById[$book->getId()] = $book;
        }
        foreach ($bookData as $bookId => $info) {
            if ($info['serie'] !== null) {
                $book = $booksById[$bookId] ?? null;
                $lang = $book?->getLanguage() ?? '-';
                $author = $book !== null ? ($book->getAuthors()[0] ?? '-') : '-';
                $key = $info['serie'].'|'.$lang;
                $seriesByLang[$key] = ($seriesByLang[$key] ?? ['serie' => $info['serie'], 'lang' => $lang, 'author' => $author, 'count' => 0]);
                $seriesByLang[$key]['count']++;
            }
        }
        usort($seriesByLang, fn (array $a, array $b) => $b['count'] <=> $a['count']);

        if ($seriesByLang !== []) {
            $io->section('Series distribution (detected):');
            $seriesRows = [];
            foreach (array_slice($seriesByLang, 0, 30) as $entry) {
                $seriesRows[] = [$entry['serie'], $entry['author'], $entry['lang'], $entry['count']];
            }
            $io->table(['Series', 'Author', 'Lang', 'Books'], $seriesRows);
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
            if (!isset($bookData[$bookId]) || in_array($bookId, $excludedIds, true)) {
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
    ): array {
        $booksData = [];
        foreach ($books as $book) {
            $booksData[] = [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'authors' => implode(', ', $book->getAuthors()),
                'language' => $book->getLanguage(),
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
4. Each book includes a "language" field (e.g. "fr", "en"). Keep titles and series names in the language indicated by this field. Do NOT translate between languages.
   - A French book (language: "fr") must keep its French title and French series name
   - An English book (language: "en") must keep its English title and English series name

IMPORTANT:
- Only return title if it needs cleaning (different from current)
- Keep series names in the book's language (do NOT translate)
- If standalone book, set serie and serieIndex to null
- Only include confident changes — if you are not familiar with a book, return null values rather than guessing
- Preserve academic annotations like "(SparkNotes)", "(CliffsNotes)", or similar study guide indicators in titles — do NOT strip them
- If a book already has "currentSerie" and/or "currentIndex", use them as hints — keep them if correct, fix them if wrong

Return a JSON object where:
- Keys are the book IDs (as strings)
- Values are objects with:
  - "title": cleaned title (string) or null if no change needed
  - "serie": series name (string) or null
  - "serieIndex": position in series (number) or null

Example: {
  "123": {"title": "Dune Messiah", "serie": "Dune", "serieIndex": 2},
  "456": {"title": null, "serie": null, "serieIndex": null},
  "789": {"title": null, "serie": "Foundation", "serieIndex": 3},
  "101": {"title": "Les enfants de Dune", "serie": "Dune", "serieIndex": 3}
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
