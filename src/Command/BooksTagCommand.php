<?php

namespace App\Command;

use App\Entity\Book;
use App\Entity\User;
use App\Suggestion\TagPrompt;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
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
        private readonly TagPrompt $tagPrompt,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userid', InputArgument::OPTIONAL, 'user for the API Key and prompts')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('userid');

        $user = $this->em->getRepository(User::class)->find($arg1);
        if (!$user instanceof User) {
            $io->error('User not found');

            return Command::FAILURE;
        }
        if ($user->getOpenAIKey() === null) {
            $io->error('User does not have an OpenAI Key');

            return Command::FAILURE;
        }

        $qb = $this->em->getRepository(Book::class)->createQueryBuilder('book');
        $qb->andWhere('book.tags = \'[]\'');
        $books = $qb->getQuery()->getResult();

        if (!is_array($books)) {
            $io->error('Failed to get books');

            return Command::FAILURE;
        }

        $progress = $io->createProgressBar(count($books));
        $this->tagPrompt->setLogger(new ConsoleLogger($output));
        foreach ($books as $book) {
            $this->tagPrompt->generateTags($book, $user);

            $this->em->flush();
            $progress->advance();
        }

        $progress->finish();

        return Command::SUCCESS;
    }
}
