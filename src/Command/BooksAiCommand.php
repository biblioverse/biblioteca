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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            ->addOption('book', 'b', InputArgument::OPTIONAL, 'book id to process (otherwise all books are processed)')
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

        ProgressBar::setFormatDefinition('custom', ' 📚 %current%/%max% [%bar%] ⌛ %message%');
        $progress = $io->createProgressBar(count($books));
        $progress->setFormat('custom');
        foreach ($books as $book) {
            $progress->setMessage($book->getSerie().' '.$book->getTitle().' ('.implode(' and ', $book->getAuthors()).')');
            $progress->advance();

            if ($type === 'summary' || $type === 'both') {
                $summaryPrompt = new SummaryPrompt($book, $user);
                $summaryPrompt = $this->contextBuilder->getContext($summaryPrompt);
                $summary = $communicator->interrogate($summaryPrompt);
                $io->block($summary, padding: true);
                $book->setSummary($summary);
            }

            if ($type === 'tags' || $type === 'both') {
                $tagPrompt = new TagPrompt($book, $user);
                $tagPrompt = $this->contextBuilder->getContext($tagPrompt);

                $array = $communicator->interrogate($tagPrompt);

                if (is_array($array)) {
                    $io->block(implode(' 🏷️ ', $array), padding: true);
                    $book->setTags($array);
                }
            }

            $this->em->flush();
        }

        $progress->finish();

        return Command::SUCCESS;
    }
}
