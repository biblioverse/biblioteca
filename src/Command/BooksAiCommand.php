<?php

namespace App\Command;

use App\Ai\AiCommunicatorInterface;
use App\Ai\CommunicatorDefiner;
use App\Ai\Context\ContextBuilder;
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
        $bookId = $input->getOption('book');
        $overwrite = $input->getOption('overwrite');

        $user = null;
        if ($userId !== null) {
            $user = $this->em->getRepository(User::class)->find($userId);
            if (!$user instanceof User) {
                $io->error('User not found');

                return Command::FAILURE;
            }
        }

        $communicator = $this->aiCommunicator->getCommunicator();

        if (!$communicator instanceof AiCommunicatorInterface) {
            $io->error('AI communicator not available');

            return Command::FAILURE;
        }

        $io->title('AI data with '.$communicator::class);

        if ($bookId === null) {
            $io->note('Processing all books without tags or summary');
            $qb = $this->em->getRepository(Book::class)->createQueryBuilder('book');
            if ($type === 'tags' || $type === 'both') {
                $qb->orWhere('book.tags = \'[]\'');
            }
            if ($type === 'summary' || $type === 'both') {
                $qb->orWhere('book.summary is null');
            }
            $books = $qb->getQuery()->getResult();
        } else {
            $io->note('Processing book '.$bookId);
            $books = $this->em->getRepository(Book::class)->findBy(['id' => $bookId]);
        }

        if (!is_array($books)) {
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
                $summaryPrompt = new SummaryPrompt($book, $user);
                $summaryPrompt = $this->contextBuilder->getContext($summaryPrompt, $output);
                $summary = $communicator->interrogate($summaryPrompt);
                $io->block($summary);
                $book->setSummary($summary);
            }

            if (($type === 'tags' || $type === 'both') && (count($book->getTags()) === 0 || $overwrite === true)) {
                $io->comment('Generating Tags');
                $tagPrompt = new TagPrompt($book, $user);
                $tagPrompt = $this->contextBuilder->getContext($tagPrompt, $output);

                $array = $communicator->interrogate($tagPrompt);

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
