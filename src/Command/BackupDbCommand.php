<?php

namespace App\Command;

use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:backup-db',
    description: 'Add a short description for your command',
)]
class BackupDbCommand extends Command
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        #[Autowire(param: 'DATABASE_URL')]
        private readonly string $dsn,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $mysqldump = (new ExecutableFinder())->find('mysqldump');

        if (null === $mysqldump) {
            $io->error('Cannot find "mysqldump" executable');

            return Command::INVALID;
        }

        $connection = $this->entityManager->getConnection();
        $backupName = 'biblioteca-backup';

        $backupDirectory = $this->projectDir.'/backups';

        $io->info(sprintf('The backup %s is in progress', $backupName));

        $database = $connection->getDatabase();
        if ($output->isVerbose()) {
            $io->comment("Backup for $database database has started");
        }

        $filePath = "$backupDirectory/$backupName-$database-".date('Y-m-d').'.sql';

        $process = Process::fromShellCommandline(
            '"${:MYSQL_DUMP}" -u "${:DB_USER}" -h "${:DB_HOST}" -P "${:DB_PORT}" "${:DB_NAME}" > "${:FILEPATH}"'
        );

        $parser = new DsnParser();
        $params = $parser->parse($this->dsn);

        $process->setPty(Process::isPtySupported());

        $process->run(null, [
            'MYSQL_DUMP' => $mysqldump,
            'DB_USER' => $params['user'], // @phpstan-ignore-line
            'DB_HOST' => $params['host'], // @phpstan-ignore-line
            'DB_PORT' => $params['port'], // @phpstan-ignore-line
            'DB_NAME' => $database,
            'MYSQL_PWD' => $params['password'], // @phpstan-ignore-line
            'FILEPATH' => $filePath,
        ]);

        if (!$process->isSuccessful()) {
            $message = '' !== $process->getErrorOutput() ? $process->getErrorOutput() : $process->getOutput();

            $io->error($message);

            return Command::FAILURE;
        }

        $finder = (new Finder())
            ->in($backupDirectory)
            ->name(["$backupName-$database-*.sql"])
            ->sortByModifiedTime()
            ->depth(['== 0'])
            ->files();

        $filesCount = $finder->count();

        /** @var array<string, \Symfony\Component\Finder\SplFileInfo> $files */
        $files = iterator_to_array($finder);

        $maxFiles = 5;
        if ($filesCount > $maxFiles) {
            $filesToDeleteCount = $filesCount - $maxFiles;
            array_splice($files, $filesToDeleteCount);

            if (1 === $filesToDeleteCount) {
                $io->warning('Reached the max backup files limit, removing the oldest one');
            } else {
                $io->warning(sprintf(
                    'Reached the max backup files limit, removing the %d oldest ones',
                    $filesToDeleteCount
                ));
            }

            foreach ($files as $file) {
                if ($output->isVerbose()) {
                    $io->comment(sprintf('Deleting "%s"', $file->getRealPath()));
                }

                $this->filesystem->remove($file->getRealPath());
            }
        }

        $io->success(sprintf('Backup %s has been successfully completed', $backupName));

        return Command::SUCCESS;
    }
}
