<?php

namespace App\Command;

use App\Entity\Book;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Orhanerday\OpenAi\OpenAi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
    public function __construct(private EntityManagerInterface $em)
    {
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

        $open_ai = new OpenAi($user->getOpenAIKey());

        $qb = $this->em->getRepository(Book::class)->createQueryBuilder('book');
        $qb->andWhere('book.tags = \'[]\'');
        $books = $qb->getQuery()->getResult();

        if (!is_array($books)) {
            $io->error('Failed to get books');

            return Command::FAILURE;
        }

        $progress = $io->createProgressBar(count($books));
        foreach ($books as $book) {
            $chat = $open_ai->chat([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful factual librarian that only refers to verifiable content to provide real answers about existing books.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->getPrompt($book, $user),
                    ],
                ],
                'temperature' => 0,
                'max_tokens' => 4000,
                'frequency_penalty' => 0,
                'presence_penalty' => 0,
            ]);

            if (!is_string($chat)) {
                $io->error('Failed to decode OpenAI response');
                continue;
            }
            $d = json_decode($chat);
            // @phpstan-ignore-next-line
            $result = $d->choices[0]->message->content;

            $result = explode("\n", $result);
            foreach ($result as $value) {
                $tag = trim($value, " \n\r\t\v\0-");
                $book->addTag($tag);
            }

            $this->em->flush();

            $progress->advance();
        }

        $progress->finish();

        return Command::SUCCESS;
    }

    private function getPrompt(Book $book, User $user): string
    {
        $prompt = (string) $user->getBookKeywordPrompt();

        $bookString = '"'.$book->getTitle().'" by '.implode(' and ', $book->getAuthors());

        if ($book->getSerie() !== null) {
            $bookString .= ' number '.$book->getSerieIndex().' in the series "'.$book->getSerie().'"';
        }

        return str_replace('{book}', $bookString, $prompt);
    }
}
