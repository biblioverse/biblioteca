<?php

namespace App\Tests;

use App\Service\BookFileSystemManager;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileSystemManagerForTests extends BookFileSystemManager
{
    public function __construct(
        Security $security,
        SluggerInterface $slugger,
        LoggerInterface $logger,
        protected string $publicDirectory,
    ) {
        $adapter = new LocalFilesystemAdapter($publicDirectory);
        $publicStorage = new Filesystem($adapter);

        parent::__construct(
            $security,
            $publicStorage,
            $publicDirectory,
            '{authorFirst}/{author}/{serie}/{title}',
            '{serie}-{serieIndex}-{title}',
            $slugger,
            $logger
        );
    }
}
