<?php
namespace App\Tests;

use App\Service\BookFileSystemManager;
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
    ){
        parent::__construct(
            $security,
            $publicDirectory,
            '{authorFirst}/{author}/{serie}/{title}',
            '{serie}-{serieIndex}-{title}',
            $slugger,
            $logger
        );
    }
}