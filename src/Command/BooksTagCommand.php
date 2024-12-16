<?php

namespace App\Command;

use App\Ai\AiCommunicatorInterface;
use App\Ai\CommunicatorDefiner;
use App\Entity\Book;
use App\Entity\User;
use App\Suggestion\TagPrompt;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'books:tag',
    description: 'Add a short description for your command',
)]
class BooksTagCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CommunicatorDefiner $aiCommunicator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userid', InputArgument::OPTIONAL, 'user for the prompts. Default prompts used if not provided')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('userid');

        $user = null;
        if ($arg1 !== null) {
            $user = $this->em->getRepository(User::class)->find($arg1);
            if (!$user instanceof User) {
                $io->error('User not found');
            }

            return Command::FAILURE;
        }

        $communicator = $this->aiCommunicator->getCommunicator();

        if (!$communicator instanceof AiCommunicatorInterface) {
            $io->error('AI communicator not available');

            return Command::FAILURE;
        }

        $qb = $this->em->getRepository(Book::class)->createQueryBuilder('book');
        $qb->andWhere('book.tags = \'[]\'');
        $books = $qb->getQuery()->getResult();

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
            $tagPrompt = new TagPrompt($book, $user);
            $array = $communicator->interrogate($tagPrompt);

            if (is_array($array)) {
                $io->writeln('🏷️ '.implode(' 🏷️ ', $array));
                $book->setTags($array);
            }

            $this->em->flush();
        }

        $progress->finish();

        return Command::SUCCESS;
    }
}
