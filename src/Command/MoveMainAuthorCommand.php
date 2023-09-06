<?php

namespace App\Command;

use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:move-main-author',
    description: 'Add a short description for your command',
)]
class MoveMainAuthorCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, private BookRepository $bookRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $books = $this->bookRepository->findAll();
        foreach ($books as $book) {
            $book->addAuthor($book->getMainAuthor());
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
