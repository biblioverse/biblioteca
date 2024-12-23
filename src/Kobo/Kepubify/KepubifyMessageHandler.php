<?php

namespace App\Kobo\Kepubify;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
class KepubifyMessageHandler
{
    public const CACHE_FOLDER = '/kepubify';
    public const TEMP_NAME_SUFFIX = 'kepub-';
    private const CACHE_TIME_SECONDS = 3 * 3600; // 3h

    public function __construct(
        #[Autowire(param: 'kernel.cache_dir')]
        private readonly string $cacheDir,
        private readonly LoggerInterface $koboKepubifyLogger,
        private readonly KepubifyEnabler $kepubifyEnabler,
        private readonly CacheItemPoolInterface $kepubifyCachePool,
    ) {
    }

    public function __invoke(KepubifyMessage $message): void
    {
        // Disable kepubify if the path is not set
        if (false === $this->kepubifyEnabler->isEnabled()) {
            $this->koboKepubifyLogger->debug('Kepubify is disabled');

            return;
        }

        // Create a temporary file
        $temporaryFile = $this->getTemporaryFilename();
        if ($temporaryFile === false) {
            $this->koboKepubifyLogger->error('Error while creating temporary file');

            return;
        }

        // Fetch the conversion result from cache
        try {
            $item = $this->kepubifyCachePool->getItem('kepubify_object_'.md5($message->source));
        } catch (InvalidArgumentException $e) {
            $this->koboKepubifyLogger->error('Error while caching kepubify: {error}', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return;
        }

        // Do the conversion and cache it
        if (false === $item->isHit()) {
            $temporaryFile = $this->convert($message, $temporaryFile);
            if ($temporaryFile === null) {
                $message->destination = null;
                $message->size = null;

                return;
            }

            $data = new KebpubifyCachedData($temporaryFile);
            $item->set($data);
            $item->expiresAfter(self::CACHE_TIME_SECONDS);
            $this->kepubifyCachePool->save($item);

            $message->size = $data->getSize();
            $message->destination = $temporaryFile;

            return;
        }

        // Restore the result from cache
        $data = $item->get();
        if (!$data instanceof KebpubifyCachedData) {
            try {
                $this->kepubifyCachePool->deleteItem($item->getKey());
            } catch (InvalidArgumentException $e) {
                $this->koboKepubifyLogger->error('Error while deleting cached kepubify data: {error}', [
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]);
            }

            return;
        }

        $result = file_put_contents($temporaryFile, $data->getContent());
        if ($result === false) {
            $this->koboKepubifyLogger->error('Error while restoring cached kepubify data');
            $temporaryFile = null;
        }
        $message->destination = $temporaryFile;
        $message->size = $data->getSize();
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

    private function convert(KepubifyMessage $message, string $temporaryFile): ?string
    {
        $filename = basename($message->source);

        $temporaryFolder = dirname($temporaryFile);

        $convertedFilename = str_replace('.epub', '.kepub.epub', $filename);

        // Run the conversion
        $process = new Process([$this->kepubifyEnabler->getKepubifyBinary(), '--inplace', '--output', $temporaryFolder, $message->source]);
        $this->koboKepubifyLogger->debug('Run kepubify command: {command}', ['command' => $process->getCommandLine()]);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->koboKepubifyLogger->error('Error while running kepubify: {output}: {error}', [
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
            ]);

            return null;
        }

        unlink($temporaryFile);

        rename($temporaryFolder.'/'.$convertedFilename, $temporaryFile);

        return $temporaryFile;
    }
}
