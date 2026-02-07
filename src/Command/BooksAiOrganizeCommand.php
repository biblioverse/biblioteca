<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'books:ai-organize',
    description: 'Use AI to organize the entire library: authors, series, and tags',
)]
class BooksAiOrganizeCommand extends Command
{
    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption('apply', 'a', InputOption::VALUE_NONE, 'Apply the changes (without this flag, only shows the proposed changes)')
            ->addOption('language', 'l', InputOption::VALUE_REQUIRED, 'Target language for tags and series (e.g., en, fr)', 'en')
            ->addOption('skip-authors', null, InputOption::VALUE_NONE, 'Skip author harmonization')
            ->addOption('skip-series', null, InputOption::VALUE_NONE, 'Skip series harmonization')
            ->addOption('skip-tags', null, InputOption::VALUE_NONE, 'Skip tag harmonization')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = $input->getOption('apply') === true;
        $language = $input->getOption('language');
        $skipAuthors = $input->getOption('skip-authors') === true;
        $skipSeries = $input->getOption('skip-series') === true;
        $skipTags = $input->getOption('skip-tags') === true;

        $io->title('AI Library Organizer');

        if (!$apply) {
            $io->warning('Running in preview mode. Use --apply to make changes.');
        }

        $steps = [];
        if (!$skipAuthors) {
            $steps[] = ['name' => 'Authors', 'command' => 'books:authors-harmonize'];
        }
        if (!$skipSeries) {
            $steps[] = ['name' => 'Series', 'command' => 'books:series-harmonize'];
        }
        if (!$skipTags) {
            $steps[] = ['name' => 'Tags', 'command' => 'books:tags-harmonize'];
        }

        if ($steps === []) {
            $io->error('All steps are skipped. Nothing to do.');

            return Command::FAILURE;
        }

        $io->note(sprintf('Will run %d steps: %s', count($steps), implode(' → ', array_column($steps, 'name'))));
        $io->newLine();

        $totalSteps = count($steps);
        $currentStep = 0;

        foreach ($steps as $step) {
            $currentStep++;
            $io->section(sprintf('Step %d/%d: %s', $currentStep, $totalSteps, $step['name']));

            $commandName = $step['command'];
            $command = $this->getApplication()?->find($commandName);

            if (!$command instanceof Command) {
                $io->error(sprintf('Command %s not found.', $commandName));

                return Command::FAILURE;
            }

            $arguments = [];

            if ($apply) {
                $arguments['--apply'] = true;
            }

            // Add language option for commands that support it
            if ($commandName === 'books:tags-harmonize') {
                $arguments['--language'] = $language;
            }

            $commandInput = new ArrayInput($arguments);
            $commandInput->setInteractive(false);

            $returnCode = $command->run($commandInput, $output);

            if ($returnCode !== Command::SUCCESS) {
                $io->warning(sprintf('Step "%s" completed with warnings or errors.', $step['name']));
            }

            $io->newLine();
        }

        $io->success('AI Library Organization complete!');

        if (!$apply) {
            $io->note('This was a preview. Run with --apply to make the changes.');
        }

        return Command::SUCCESS;
    }
}
