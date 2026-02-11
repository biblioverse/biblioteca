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
    name: 'books:authors-harmonize',
    description: 'Use AI to harmonize author names across the library',
)]
class BooksAuthorsHarmonizeCommand extends Command
{
    private const int BATCH_SIZE = 30;

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
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = $input->getOption('apply') === true;

        $communicator = $this->aiCommunicator->getCommunicator(AiAction::Assistant);

        if (!$communicator instanceof AiCommunicatorInterface) {
            $io->error('AI communicator not available. Please configure an AI model in settings.');

            return Command::FAILURE;
        }

        $io->title('Author Harmonization with AI ('.$communicator::class.')');

        // Collect all unique authors
        $authorBooks = $this->collectAuthorBooks();

        if ($authorBooks === []) {
            $io->success('No authors found.');

            return Command::SUCCESS;
        }

        $uniqueAuthors = array_keys($authorBooks);
        $io->note(sprintf('Found %d unique author names.', count($uniqueAuthors)));

        // Process in batches
        $batches = array_chunk($uniqueAuthors, self::BATCH_SIZE);
        $io->note(sprintf('Processing in %d batches of up to %d authors each.', count($batches), self::BATCH_SIZE));

        /** @var array<string, string> $authorMapping old name => canonical name */
        $authorMapping = [];
        $progressBar = $io->createProgressBar(count($batches));
        $progressBar->start();

        foreach ($batches as $batch) {
            $result = $this->analyzeAuthorBatch($communicator, $batch);
            foreach ($result as $oldName => $canonicalName) {
                if ($oldName !== $canonicalName) {
                    $authorMapping[$oldName] = $canonicalName;
                }
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        if ($authorMapping === []) {
            $io->success('All author names are already harmonized.');

            return Command::SUCCESS;
        }

        // Show proposed changes
        $io->section('Proposed author harmonization:');
        $rows = [];
        $affectedBooks = 0;
        foreach ($authorMapping as $oldName => $canonicalName) {
            $bookCount = count($authorBooks[$oldName] ?? []);
            $affectedBooks += $bookCount;
            $rows[] = [$oldName, $canonicalName, $bookCount];
        }

        usort($rows, fn ($a, $b) => $b[2] <=> $a[2]); // Sort by book count
        $io->table(['Current Name', 'Canonical Name', 'Books'], $rows);

        $io->note(sprintf('%d author names will be harmonized, affecting %d books.', count($authorMapping), $affectedBooks));

        if (!$apply) {
            $io->note('Run with --apply to apply these changes.');

            return Command::SUCCESS;
        }

        // Apply changes
        $io->section('Applying author harmonization...');
        $updatedCount = $this->applyAuthorMapping($authorMapping, $authorBooks);

        $this->em->flush();

        $io->success(sprintf('Author harmonization complete. Updated %d books.', $updatedCount));

        return Command::SUCCESS;
    }

    /**
     * Collect all books grouped by author name.
     *
     * @return array<string, Book[]>
     */
    private function collectAuthorBooks(): array
    {
        $books = $this->bookRepository->findAll();
        $authorBooks = [];

        foreach ($books as $book) {
            $authors = $book->getAuthors();
            foreach ($authors as $author) {
                if ($author !== '') {
                    $authorBooks[$author][] = $book;
                }
            }
        }

        return $authorBooks;
    }

    /**
     * @param string[] $authors
     * @return array<string, string> old name => canonical name
     */
    private function analyzeAuthorBatch(
        AiCommunicatorInterface $communicator,
        array $authors,
    ): array {
        $authorsJson = json_encode($authors, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You are an expert librarian specializing in author cataloging.

TASK: Harmonize these author names by identifying duplicates and variations.

AUTHOR NAMES TO ANALYZE:
{$authorsJson}

IMPORTANT: Some entries may contain multiple authors separated by commas or "and".
In that case, you MUST preserve ALL authors. Split them into individual names, harmonize each one, and return them as a comma-separated list.
NEVER drop any author from a multi-author entry.

For each author name:
1. Identify if it's a variation of another name in the list (e.g., "J.R.R. Tolkien", "JRR Tolkien", "Tolkien, J.R.R.")
2. Choose the most complete and standard form as the canonical name
3. Use format: "Firstname Lastname" (not "Lastname, Firstname")
4. Keep accents and special characters
5. For authors with known pen names, use their most famous name
6. If you are not familiar with an author, return their name unchanged rather than guessing

Return a JSON object where:
- Keys are the original author names
- Values are the canonical names (comma-separated if multiple authors)
- Include ALL names, even if unchanged

Example: {"J.R.R. Tolkien": "J.R.R. Tolkien", "JRR Tolkien": "J.R.R. Tolkien", "Tolkien, John Ronald Reuel": "J.R.R. Tolkien", "Bob Smith and Alice Foo": "Bob Smith, Alice Foo", "Bob Smith, Alice Foo": "Bob Smith, Alice Foo"}

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

            // Validate: only return string => string mappings
            $validated = [];
            foreach ($mapping as $oldName => $newName) {
                if (is_string($oldName) && is_string($newName)) {
                    $validated[$oldName] = $newName;
                }
            }

            return $validated;
        } catch (\JsonException) {
            return [];
        }
    }

    /**
     * @param array<string, string> $authorMapping
     * @param array<string, Book[]> $authorBooks
     */
    private function applyAuthorMapping(array $authorMapping, array $authorBooks): int
    {
        $updatedBooks = [];

        foreach ($authorMapping as $oldName => $canonicalName) {
            $books = $authorBooks[$oldName] ?? [];
            foreach ($books as $book) {
                $bookId = $book->getId();
                if (!isset($updatedBooks[$bookId])) {
                    $updatedBooks[$bookId] = $book;
                }

                $currentAuthors = $book->getAuthors();
                $newAuthors = [];
                $modified = false;

                foreach ($currentAuthors as $author) {
                    if ($author === $oldName) {
                        // Canonical name may contain multiple authors (comma-separated)
                        $splitAuthors = array_map('trim', explode(',', $canonicalName));
                        array_push($newAuthors, ...$splitAuthors);
                        $modified = true;
                    } else {
                        $newAuthors[] = $author;
                    }
                }

                if ($modified) {
                    // Remove duplicates that might arise from merging
                    $newAuthors = array_unique($newAuthors);
                    $book->setAuthors(array_values($newAuthors));
                }
            }
        }

        return count($updatedBooks);
    }
}
