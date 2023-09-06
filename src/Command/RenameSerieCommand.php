<?php

namespace App\Command;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:rename-serie',
    description: 'Add a short description for your command',
)]
class RenameSerieCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, private BookRepository $bookRepository, private SluggerInterface $slugger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('serie', InputArgument::REQUIRED, 'Serie to rename')
            ->addArgument('newname', InputArgument::REQUIRED, 'new name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $toRename = $input->getArgument('serie');
        $newName = $input->getArgument('newname');

        if (!is_string($toRename) || !is_string($newName)) {
            throw new \Exception('Arguments must be strings');
        }
        /** @var Book[] $books */
        $books = $this->bookRepository->getBySerieQuery($this->slugger->slug($toRename))->getResult();

        foreach ($books as $book) {
            $io->writeln('Renaming '.$toRename.' to '.$newName.' in '.$book->getTitle());
            $book->setSerie($newName);
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
