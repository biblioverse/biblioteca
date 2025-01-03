<?php

namespace App\Command;

use App\Repository\BookRepository;
use App\Service\BookFileSystemManagerInterface;
use SebLucas\EPubMeta\EPub;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

#[AsCommand(
    name: 'app:write-metadata',
    description: 'Write epub metadata',
)]
class WriteMetadataCommand extends Command
{
    public function __construct(private readonly BookRepository $bookRepository, private FileSystem $filesystem, private readonly BookFileSystemManagerInterface $bookFileSystemManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('no-backup', 'b', InputOption::VALUE_NONE, 'Do not backup file before writing')
            ->addArgument('book-id', InputOption::VALUE_REQUIRED, 'Which book to write, use "all" for all books')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $bookId = $input->getArgument('book-id');
        $nobackup = $input->getOption('no-backup');
        $books = $bookId === 'all' ? $this->bookRepository->findAll() : $this->bookRepository->findBy(['id' => $bookId]);

        if (count($books) === 0) {
            $io->error('No books found');
            return Command::FAILURE;
        }

        $io->note(sprintf('Processing: %s book(s)', count($books)));

        $progress = $io->createProgressBar(count($books));
        $progress->setFormat('verbose');
        foreach ($books as $book) {
            if ($book->getExtension() !== 'epub') {
                $progress->setMessage('Skipping '.$book->getTitle().', not an epub file');
                continue;
            }
            try {
                $file = $this->bookFileSystemManager->getBookFile($book);

                if($nobackup !== true) {
                    $this->filesystem->copy($file->getRealPath(), $file->getRealPath() . '.bak', true);
                }

                $epub = new EPub($file->getRealPath());

                $epub->setTitle($book->getTitle());
                $epub->setAuthors($book->getAuthors());
                $epub->setDescription($book->getSummary());
                $epub->setSubjects($book->getTags());
                $epub->setSeries($book->getSerie());
                $epub->setSeriesIndex($book->getSerieIndex());
                $epub->save();
                $io->success(''. $book->getTitle().' saved');

            } catch (\Exception $e) {
                $progress->setMessage($e->getMessage());
                continue;
            }
            $progress->advance();
        }
        $progress->finish();



        return Command::SUCCESS;
    }
}
