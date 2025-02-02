<?php

namespace App\Tests\Kobo;

use App\Kobo\DownloadHelper;
use App\Tests\TestCaseHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DownloadHelperTest extends KernelTestCase
{
    use TestCaseHelperTrait;

    private function getBookFileName(): string
    {
        return dirname(__DIR__, 1).'/Resources/books/real-TheOdysses.epub';
    }

    public function testSize(): void
    {
        $assetPath = $this->getBookFileName();
        self::assertFileExists($assetPath, 'Asset not found');
        $size = (int) filesize($assetPath);
        $downloadHelper = $this->getService(DownloadHelper::class);
        self::assertGreaterThan(0, $size, 'invalid file size');
        self::assertSame($size, $downloadHelper->getSize($this->getBook()), 'invalid file size');
    }

    public function testExists(): void
    {
        $assetPath = $this->getBookFileName();
        self::assertFileExists($assetPath, 'Asset not found');

        $downloadHelper = $this->getService(DownloadHelper::class);
        self::assertTrue($downloadHelper->exists($this->getBook()), 'book file should exists');
    }
}
