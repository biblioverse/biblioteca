<?php

namespace App\Command;

use App\Ai\Communicator\AiAction;
use App\Ai\Communicator\AiCommunicatorInterface;
use App\Ai\Communicator\CommunicatorDefiner;
use App\Ai\Context\ContextBuilder;
use App\Ai\Prompt\PromptFactory;
use App\Ai\Prompt\SummaryPrompt;
use App\Ai\Prompt\TagPrompt;
use App\Entity\Book;
use App\Entity\Suggestion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'books:ai',
    description: 'Add a short description for your command',
)]
class BooksAiCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CommunicatorDefiner $aiCommunicator,
        private readonly ContextBuilder $contextBuilder,
        private readonly PromptFactory $promptFactory,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::REQUIRED, '`summary`, `tags` or `both`')
            ->addArgument('language', InputArgument::OPTIONAL, 'fr, en, etc')
            ->addOption('book', 'b', InputOption::VALUE_REQUIRED, 'book id to process (otherwise all books are processed)')
            ->addOption('overwrite', 'o', InputOption::VALUE_NONE, 'If a value for the fields is present, overwrite it.')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $type = $input->getArgument('type');
        $language = $input->getArgument('language');

        if (!is_string($language)) {
            $language = null;
        }

        if (!in_array($type, ['summary', 'tags', 'both'], true)) {
            $io->error('Invalid type');

            return Command::FAILURE;
        }
        /** @var string|null $bookId */
        $bookId = $input->getOption('book');
        $bookId = $bookId === '' || $bookId === null ? null : (int) $bookId;

        $overwrite = $input->getOption('overwrite');

        $communicator = $this->aiCommunicator->getCommunicator(AiAction::Assistant);

        if (!$communicator instanceof AiCommunicatorInterface) {
            $io->error('AI communicator not available');

            return Command::FAILURE;
        }

        $io->title('Communicator data with '.$communicator::class);

        if ($bookId === null) {
            $io->note('Processing all books without tags or summary');
            $qb = $this->em->getRepository(Book::class)->createQueryBuilder('book');
            if ($type === 'tags' || $type === 'both') {
                $qb->orWhere('book.tags = \'[]\'');
                $qb->orWhere('book.tags = \'[""]\'');
                $qb->orWhere('book.tags is null');
            }
            if ($type === 'summary' || $type === 'both') {
                $qb->orWhere('book.summary is null');
                $qb->orWhere('book.summary =\'\'');
            }
            /** @var Book[] $books */
            $books = $qb->getQuery()->getResult();
        } else {
            $io->note('Processing book '.$bookId);
            /** @var Book[] $books */
            $books = $this->em->getRepository(Book::class)->findBy(['id' => $bookId]);
        }

        if ($books === []) {
            $io->error('Failed to get books');

            return Command::FAILURE;
        }

        $total = count($books);
        $current = 1;
        foreach ($books as $book) {
            $currentPad = str_pad((string) $current, strlen((string) $total), ' ', STR_PAD_LEFT);

            $io->section($currentPad.'/'.$total.': '.$book->getSerie().' '.$book->getTitle().' ('.implode(' and ', $book->getAuthors()).')');

            $currentSuggestions = $book->getSuggestions()->toArray();
            if (($type === 'summary' || $type === 'both') && (trim((string) $book->getSummary()) === '' || $overwrite === true)) {
                $summarySuggestions = array_filter($currentSuggestions, fn (Suggestion $suggestion) => $suggestion->getField() === 'summary');
                $io->comment('Generating Summary');
                if (count($summarySuggestions) === 0 || $overwrite === true) {
                    $summaryPrompt = $this->promptFactory->getPrompt(SummaryPrompt::class, $book, $language);
                    $summaryPrompt = $this->contextBuilder->getContext($communicator->getAiModel(), $summaryPrompt, $output);
                    $summary = $communicator->interrogate($summaryPrompt->getPrompt());
                    $summary = $summaryPrompt->convertResult($summary);
                    $io->block($summary);
                    if (is_string($summary)) {
                        $suggestion = new Suggestion();
                        $suggestion->setBook($book);
                        $suggestion->setField('summary');
                        $suggestion->setSuggestion($summary);

                        $this->em->persist($suggestion);
                    }
                } else {
                    $io->comment('Summary suggestion already present');
                }
            }

            if (($type === 'tags' || $type === 'both') && ($book->getTags() === [] || $book->getTags() === null || $overwrite === true)) {
                $io->comment('Generating Tags');
                $tagSuggestions = array_filter($currentSuggestions, fn (Suggestion $suggestion) => $suggestion->getField() === 'tags');
                if (count($tagSuggestions) === 0 || $overwrite === true) {
                    $tagPrompt = $this->promptFactory->getPrompt(TagPrompt::class, $book, $language);
                    $tagPrompt = $this->contextBuilder->getContext($communicator->getAiModel(), $tagPrompt);

                    $array = $communicator->interrogate($tagPrompt->getPrompt());

                    $array = $tagPrompt->convertResult($array);

                    if (is_array($array)) {
                        $io->block(implode(' 🏷️ ', $array));
                        $suggestion = new Suggestion();
                        $suggestion->setBook($book);
                        $suggestion->setField('tags');
                        $suggestion->setSuggestion(json_encode($array, JSON_THROW_ON_ERROR));

                        $this->em->persist($suggestion);
                    }
                } else {
                    $io->comment('Tag suggestion already present');
                }
            }

            $this->em->flush();
            $current++;
        }

        return Command::SUCCESS;
    }
}
