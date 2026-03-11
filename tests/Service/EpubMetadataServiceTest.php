<?php

namespace App\Tests\Service;

use App\Entity\Book;
use App\Service\EpubMetadataService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class EpubMetadataServiceTest extends TestCase
{
    private EpubMetadataService $service;
    private Filesystem $filesystem;
    private string $testEpubPath;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->service = new EpubMetadataService();
        $this->filesystem = new Filesystem();

        // Create a temporary directory for test files
        $this->tempDir = sys_get_temp_dir().'/epub_test_'.uniqid();
        $this->filesystem->mkdir($this->tempDir);

        // Create a minimal test EPUB file
        $this->testEpubPath = $this->createTestEpub();
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if ($this->filesystem->exists($this->tempDir)) {
            $this->filesystem->remove($this->tempDir);
        }
    }

    public function testEmbedMetadataWithValidEpub(): void
    {
        // Create a test book entity
        $book = new Book();
        $book->setTitle('Test Book Title');
        $book->addAuthor('Test Author');
        $book->setSummary('This is a test book summary.');
        $book->setPublisher('Test Publisher');
        $book->setLanguage('en');
        $book->setTags(['fiction', 'test']);
        $book->setSerie('Test Series');
        $book->setSerieIndex(1.0);

        // Embed metadata
        $resultPath = $this->service->embedMetadata($book, new \SplFileInfo($this->testEpubPath));

        // Verify that a new file was created
        self::assertFileExists($resultPath);
        self::assertNotEquals($this->testEpubPath, $resultPath);

        // Extract and verify the metadata
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($resultPath));

        // Find OPF file
        $opfContent = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($filename !== false && pathinfo($filename, PATHINFO_EXTENSION) === 'opf') {
                $opfContent = $zip->getFromIndex($i);
                break;
            }
        }
        $zip->close();

        self::assertNotNull($opfContent);
        self::assertIsString($opfContent);

        // Verify metadata in OPF
        self::assertStringContainsString('Test Book Title', $opfContent);
        self::assertStringContainsString('Test Author', $opfContent);
        self::assertStringContainsString('This is a test book summary.', $opfContent);
        self::assertStringContainsString('Test Publisher', $opfContent);
        self::assertStringContainsString('<dc:language>en</dc:language>', $opfContent);
        self::assertStringContainsString('Fiction', $opfContent);
        self::assertStringContainsString('Test', $opfContent);
        self::assertStringContainsString('calibre:series', $opfContent);
        self::assertStringContainsString('Test Series', $opfContent);

        // Clean up
        $this->filesystem->remove($resultPath);
    }

    public function testEmbedMetadataWithNonExistentFile(): void
    {
        $book = new Book();
        $book->setTitle('Test Book');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Original EPUB file not found');

        $this->service->embedMetadata($book, new \SplFileInfo('/path/to/nonexistent/file.epub'));
    }

    public function testEmbedMetadataWithInvalidEpub(): void
    {
        // Create an invalid EPUB (just a text file)
        $invalidEpubPath = $this->tempDir.'/invalid.epub';
        file_put_contents($invalidEpubPath, 'This is not a valid EPUB file');

        $book = new Book();
        $book->setTitle('Test Book');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not open EPUB file');

        $this->service->embedMetadata($book, new \SplFileInfo($invalidEpubPath));
    }

    public function testEmbedMetadataWithMinimalBook(): void
    {
        // Test with a book that has minimal metadata
        $book = new Book();
        $book->setTitle('Minimal Book');
        $book->addAuthor('Unknown Author');

        $resultPath = $this->service->embedMetadata($book, new \SplFileInfo($this->testEpubPath));

        self::assertFileExists($resultPath);

        // Extract and verify minimal metadata
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($resultPath));

        $opfContent = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($filename !== false && pathinfo($filename, PATHINFO_EXTENSION) === 'opf') {
                $opfContent = $zip->getFromIndex($i);
                break;
            }
        }
        $zip->close();

        self::assertNotNull($opfContent);
        self::assertIsString($opfContent);
        self::assertStringContainsString('Minimal Book', $opfContent);
        self::assertStringContainsString('Unknown Author', $opfContent);

        // Clean up
        $this->filesystem->remove($resultPath);
    }

    /**
     * Create a minimal valid EPUB file for testing
     */
    private function createTestEpub(): string
    {
        $epubPath = $this->tempDir.'/test.epub';
        $epubDir = $this->tempDir.'/epub_content';

        // Create directory structure
        $this->filesystem->mkdir($epubDir.'/META-INF');
        $this->filesystem->mkdir($epubDir.'/OEBPS');

        // Create mimetype file
        file_put_contents($epubDir.'/mimetype', 'application/epub+zip');

        // Create container.xml
        $containerXml = '<?xml version="1.0" encoding="UTF-8"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
    <rootfiles>
        <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
    </rootfiles>
</container>';
        file_put_contents($epubDir.'/META-INF/container.xml', $containerXml);

        // Create content.opf
        $contentOpf = '<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://www.idpf.org/2007/opf" unique-identifier="uid" version="2.0">
    <metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">
        <dc:identifier id="uid">test-book-id</dc:identifier>
        <dc:title>Original Title</dc:title>
        <dc:creator opf:role="aut">Original Author</dc:creator>
        <dc:language>en</dc:language>
    </metadata>
    <manifest>
        <item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>
        <item id="content" href="content.xhtml" media-type="application/xhtml+xml"/>
    </manifest>
    <spine toc="ncx">
        <itemref idref="content"/>
    </spine>
</package>';
        file_put_contents($epubDir.'/OEBPS/content.opf', $contentOpf);

        // Create a simple content file
        $contentXhtml = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Test Content</title>
</head>
<body>
    <h1>Test Book</h1>
    <p>This is test content.</p>
</body>
</html>';
        file_put_contents($epubDir.'/OEBPS/content.xhtml', $contentXhtml);

        // Create NCX file
        $tocNcx = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE ncx PUBLIC "-//NISO//DTD ncx 2005-1//EN" "http://www.daisy.org/z3986/2005/ncx-2005-1.dtd">
<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1">
    <head>
        <meta name="dtb:uid" content="test-book-id"/>
    </head>
    <docTitle>
        <text>Test Book</text>
    </docTitle>
    <navMap>
        <navPoint id="navpoint-1" playOrder="1">
            <navLabel>
                <text>Chapter 1</text>
            </navLabel>
            <content src="content.xhtml"/>
        </navPoint>
    </navMap>
</ncx>';
        file_put_contents($epubDir.'/OEBPS/toc.ncx', $tocNcx);

        // Create EPUB zip file
        $zip = new \ZipArchive();
        $zip->open($epubPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Add mimetype first (uncompressed)
        $zip->addFile($epubDir.'/mimetype', 'mimetype');
        $zip->setCompressionName('mimetype', \ZipArchive::CM_STORE);

        // Add other files
        $zip->addFile($epubDir.'/META-INF/container.xml', 'META-INF/container.xml');
        $zip->addFile($epubDir.'/OEBPS/content.opf', 'OEBPS/content.opf');
        $zip->addFile($epubDir.'/OEBPS/content.xhtml', 'OEBPS/content.xhtml');
        $zip->addFile($epubDir.'/OEBPS/toc.ncx', 'OEBPS/toc.ncx');

        $zip->close();

        return $epubPath;
    }
}
