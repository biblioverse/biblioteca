<?php
namespace App\Tests;

use App\Service\BookFileSystemManager;
use Psr\Log\NullLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

trait InjectFakeFileSystemTrait
{
    public function injectFakeFileSystemManager(): void
    {
        $resources = __DIR__.'/Resources';
        $realPath = realpath($resources);
        self::assertNotFalse($realPath);
        $fixtureBookPath = $realPath."/";
        $mockBuilder = $this->getMockBuilder(BookFileSystemManager::class);

        $DEFAULT_BOOK_FOLDER_NAMING_FORMAT = '{authorFirst}/{author}/{title}/{serie}';
        $DEFAULT_BOOK_FILE_NAMING_FORMAT = '{serie}-{title}';

        $mock =  $mockBuilder->setConstructorArgs([
            self::getContainer()->get(Security::class),
            realpath($resources),
            $DEFAULT_BOOK_FOLDER_NAMING_FORMAT,
            $DEFAULT_BOOK_FILE_NAMING_FORMAT,
            $this->createMock(SluggerInterface::class),
            new NullLogger(),
        ])
            ->onlyMethods(['getBooksDirectory', 'getCoverDirectory'])
            ->enableProxyingToOriginalMethods()
            ->getMock();
        $mock->expects(self::any())->method('getBooksDirectory')->willReturn($fixtureBookPath);
        $mock->expects(self::any())->method('getCoverDirectory')->willReturn($fixtureBookPath);

        self::assertSame(realpath($resources).'/books/TheOdysses.epub', $mock->getBookFilename($this->getBook()), "Faking Filesystem failed");
        self::getContainer()->set(BookFileSystemManager::class, $mock);
    }
}