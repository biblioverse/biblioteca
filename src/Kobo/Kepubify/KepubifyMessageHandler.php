<?php

namespace App\Kobo\Kepubify;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
class KepubifyMessageHandler
{
    public const CACHE_FOLDER = '/kepubify';
    public const TEMP_NAME_SUFFIX = 'kepub-';

    public function __construct(
        #[Autowire(param: 'kernel.cache_dir')]
        private readonly string $cacheDir,
        private readonly LoggerInterface $logger,
        private readonly KepubifyEnabler $kepubifyEnabler
    ) {
    }

    public function __invoke(KepubifyMessage $message): void
    {
        // Disable kepubify if the path is not set
        if (false === $this->kepubifyEnabler->isEnabled()) {
            return;
        }

        // Create a temporary file
        $temporaryFile = $this->getTemporaryFilename();
        if ($temporaryFile === false) {
            $this->logger->error('Error while creating temporary file');

            return;
        }

        // Run the conversion
        $process = new Process([$this->kepubifyEnabler->getKepubifyBinary(), '--output', $temporaryFile, $message->source]);
        $this->logger->debug('Run kepubify command: {command}', ['command' => $process->getCommandLine()]);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->logger->error('Error while running kepubify: {output}: {error}', [
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
            ]);
            @unlink($temporaryFile);

            return;
        }

        $message->destination = $temporaryFile;
    }

    /**
     * Create a temporary file to handle the conversion result.
     * Note that the name must be unique to handle concurrent requests
     * because the file will be deleted once the request is served.
     *
     * @return string|false
     */
    private function getTemporaryFilename(): string|false
    {
        $dir = $this->cacheDir.self::CACHE_FOLDER;
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            return false;
        }

        return tempnam($dir, self::TEMP_NAME_SUFFIX);
    }
}
