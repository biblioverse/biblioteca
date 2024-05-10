<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class RunCommandManuallyController extends AbstractController
{
    public function __construct(protected KernelInterface $kernel)
    {
    }

    #[Route('/admin/commands', name: 'app_run_command_manually')]
    public function index(): Response
    {
        return $this->render('run_command_manually/index.html.twig', [
            'commands' => $this->getCommands(),
        ]);
    }

    #[Route('/admin/commands/run/{name}', name: 'run_command')]
    public function run(string $name): Response
    {
        $commands = array_filter($this->getCommands(), function (array $command) use ($name) {
            return $command['action'] == $name;
        });
        if (count($commands) < 1) {
            throw $this->createNotFoundException(sprintf('Command "%s" does not exist.', $name));
        }
        $command = end($commands);

        return $this->execute($command);
    }

    protected function execute(array $command): Response
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        // Replace "your:command" with the actual command you want to run
        $input = $command['input'];

        // You can use a BufferedOutput to capture the command output
        $output = new BufferedOutput();

        // Run the command
        $exitCode = $application->run($input, $output);

        $outputContent = $output->fetch();

        // Do something with the command output or any other logic

        return $this->render('run_command_manually/result.html.twig', ['output' => $outputContent, 'exitCode' => $exitCode]);
    }

    private function getCommands(): array
    {
        return [
            'scan all books' => [
                'action' => 'scan_all_books',
                'input' => new ArrayInput([
                    'command' => 'books:scan',
                ]),
            ],
            'check existing books' => [
                'action' => 'check_books',
                'input' => new ArrayInput([
                    'command' => 'books:check',
                ]),
            ],
            'extract covers' => [
                'action' => 'extract_covers',
                'input' => new ArrayInput([
                    'command' => 'books:extract-cover',
                    'book-id' => 'all',
                ]),
            ],
        ];
    }
}
