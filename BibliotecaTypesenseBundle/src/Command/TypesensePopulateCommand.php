<?php

namespace Biblioteca\TypesenseBundle\Command;

use Biblioteca\TypesenseBundle\Mapper\MapperInterface;
use Biblioteca\TypesenseBundle\Mapper\MapperLocator;
use Biblioteca\TypesenseBundle\PopulateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'biblioteca:typesense:populate',
    description: 'Populate Typesense collections',
)]
class TypesensePopulateCommand extends Command
{
    public function __construct(
        private PopulateService $populateService,
        private MapperLocator $mapperLocator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->mapperLocator->count();
        if ($count === 0) {
            $io->warning('No mappers found. Declare at least one service implementing '.MapperInterface::class);

            return Command::SUCCESS;
        }

        $progress = new ProgressBar($output, 0);

        foreach ($this->mapperLocator->getMappers() as $mapper) {
            $name = $mapper->getMapping()->getName();
            $io->writeln('Deleting collection '.$name);
            $this->populateService->deleteCollection($mapper);

            $io->writeln('Creating collection '.$name);
            $this->populateService->createCollection($mapper);

            $io->writeln('Filling collection '.$name);
            $progress->start($mapper->getDataCount());
            foreach ($this->populateService->fillCollection($mapper) as $_) {
                $progress->advance();
            }
            $progress->clear();
        }
        $progress->finish();

        $io->success('Finished');

        return Command::SUCCESS;
    }
}
