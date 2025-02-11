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
use App\Entity\User;
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
            ->addArgument('userid', InputArgument::OPTIONAL, 'user for the prompts. Default prompts used if not provided')
            ->addOption('book', 'b', InputOption::VALUE_REQUIRED, 'book id to process (otherwise all books are processed)')
            ->addOption('overwrite', 'o', InputOption::VALUE_NONE, 'If a value for the fields is present, overwrite it.')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('userid');

        $type = $input->getArgument('type');
        if (!in_array($type, ['summary', 'tags', 'both'], true)) {
            $io->error('Invalid type');

            return Command::FAILURE;
        }
        /** @var string|null $bookId */
        $bookId = $input->getOption('book');
        $bookId = $bookId === '' || $bookId === null ? null : (int) $bookId;

        $overwrite = $input->getOption('overwrite');

        $user = null;
        if ($userId !== null) {
            $user = $this->em->getRepository(User::class)->find($userId);
            if (!$user instanceof User) {
                $io->error('User not found');

                return Command::FAILURE;
            }
        }

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
            }
            if ($type === 'summary' || $type === 'both') {
                $qb->orWhere('book.summary is null');
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

            if (($type === 'summary' || $type === 'both') && (trim((string) $book->getSummary()) === '' || $overwrite === true)) {
                $io->comment('Generating Summary');
                $summaryPrompt = $this->promptFactory->getPrompt(SummaryPrompt::class, $book, $user);
                $summaryPrompt = $this->contextBuilder->getContext($communicator->getAiModel(), $summaryPrompt, $output);
                $summary = $communicator->interrogate($summaryPrompt->getPrompt());
                $summary = $summaryPrompt->convertResult($summary);
                $io->block($summary);
                if (is_string($summary)) {
                    $book->setSummary($summary);
                }
            }

            if (($type === 'tags' || $type === 'both') && ($book->getTags() === [] || $book->getTags() === null || $overwrite === true)) {
                $io->comment('Generating Tags');
                $tagPrompt = $this->promptFactory->getPrompt(TagPrompt::class, $book, $user);
                $tagPrompt = $this->contextBuilder->getContext($communicator->getAiModel(), $tagPrompt, $output);

                $array = $communicator->interrogate($tagPrompt->getPrompt());

                $array = $tagPrompt->convertResult($array);

                if (is_array($array)) {
                    $io->block(implode(' 🏷️ ', $array));
                    $book->setTags($array);
                }
            }

            $this->em->flush();
            $current++;
        }

        return Command::SUCCESS;
    }
}
