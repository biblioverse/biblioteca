<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'translation:retrieve-from-profiler',
    description: 'Add a short description for your command',
)]
class TranslationRetrieveFromProfilerCommand extends Command
{
    public function __construct(private readonly ?Profiler $profiler, private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->profiler instanceof Profiler) {
            throw new \RuntimeException('Profiler not available');
        }
        $things = $this->profiler->find('', '', 1, '', '', '', '200');

        $io = new SymfonyStyle($input, $output);

        foreach ($things as $thing) {
            $prof = $this->profiler->loadProfile($thing['token']);

            if ($prof instanceof Profile && $prof->hasCollector('translation')) {
                $collector = $prof->getCollector('translation');
                /** @var \Symfony\Component\Translation\DataCollector\TranslationDataCollector $collector */

                /** @var Data $collector_messages */
                $collector_messages = $collector->getMessages();
                $raw_values = $collector_messages->getValue(true);

                if (!is_array($raw_values) || $raw_values === []) {
                    $io->writeln('No messages found');
                    continue;
                }

                $filtered_values = array_filter($raw_values, fn ($value) => $value['state'] !== 0);

                $grouped = self::groupBy($filtered_values, 'domain');

                foreach ($grouped as $domain => $values) {
                    $byLocale = self::groupBy($values, 'locale');
                    foreach (array_keys($byLocale) as $locale) {
                        $filename = $domain;
                        if ($domain === 'messages') {
                            $filename .= MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;
                        }
                        $filename .= '.'.$locale.'.yaml';
                        $filename = $this->kernel->getProjectDir().'/translations/'.$filename;

                        $yaml = Yaml::parseFile($filename);
                        if (!is_array($yaml)) {
                            throw new \RuntimeException('Invalid yaml file');
                        }

                        $io->writeln('Adding in file: '.$filename);
                        $io->writeln('---------------------');
                        $contents = [];
                        foreach ($values as $message) {
                            if (array_key_exists($message['id'], $yaml)) {
                                continue;
                            }
                            $line = '"'.$message['id'].'": "__'.$message['translation'].'"';
                            $contents[] = $line;
                            $io->writeln($line);
                        }
                        if ($contents === []) {
                            $io->writeln('No new translations');
                            continue;
                        }
                        file_put_contents($filename, implode("\n", $contents)."\n", FILE_APPEND);

                        $io->writeln('');
                    }
                }
            }
        }

        return Command::SUCCESS;
    }

    public static function groupBy(array $array, string $columnName): array
    {
        $newArray = [];
        foreach ($array as $value) {
            $index = $value[$columnName];
            $newArray[$index][] = $value;
        }

        return $newArray;
    }
}
