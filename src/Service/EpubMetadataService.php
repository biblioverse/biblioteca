<?php

namespace App\Service;

use App\Entity\Book;
use Symfony\Component\Filesystem\Filesystem;

class EpubMetadataService
{
    private const string NS_DC = 'http://purl.org/dc/elements/1.1/';
    private const string NS_OPF = 'http://www.idpf.org/2007/opf';

    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly bool $metadataEmbeddingEnabled = true,
    ) {
        $this->filesystem = new Filesystem();
    }

    /**
     * Creates a temporary EPUB file with embedded metadata from the database.
     * This works by extracting the EPUB, modifying the OPF file, and re-zipping.
     *
     * @param Book $book The book entity with metadata
     * @param \SplFileInfo $originalPath Path to the original EPUB file
     * @throws \RuntimeException if the original EPUB file is not found, cannot be opened, or the OPF file is missing
     * @throws \Exception if any other error occurs during the process
     */
    public function embedMetadata(Book $book, \SplFileInfo $originalPath): \SplFileInfo
    {
        if (!file_exists($originalPath->getRealPath())) {
            throw new \RuntimeException('Original EPUB file not found: '.$originalPath->getRealPath());
        }

        if ($this->metadataEmbeddingEnabled === false) {
            return $originalPath;
        }

        // Create temporary directories
        $tempDir = sys_get_temp_dir().'/'.uniqid('epub_extract_', true);
        $tempPath = sys_get_temp_dir().'/'.uniqid('epub_metadata_', true).'.epub';

        try {
            // Extract the EPUB file
            $zip = new \ZipArchive();
            if ($zip->open($originalPath) !== true) {
                throw new \RuntimeException('Could not open EPUB file');
            }

            $zip->extractTo($tempDir);
            $zip->close();

            // Find and modify the OPF file
            $opfPath = $this->findOpfFile($tempDir);
            if ($opfPath === null) {
                throw new \RuntimeException('Could not find OPF file in EPUB');
            }

            $this->modifyOpfMetadata($opfPath, $book);

            // Re-create the EPUB file
            $this->createEpubFromDirectory($tempDir, $tempPath);

            // Clean up extraction directory
            $this->filesystem->remove($tempDir);
            $this->filesystem->remove($originalPath);

            return new \SplFileInfo($tempPath);
        } catch (\Exception $e) {
            // Clean up on error
            if ($this->filesystem->exists($tempDir)) {
                $this->filesystem->remove($tempDir);
            }
            if ($this->filesystem->exists($tempPath)) {
                $this->filesystem->remove($tempPath);
            }
            throw $e;
        }
    }

    /**
     * Find the OPF file in the extracted EPUB directory
     */
    private function findOpfFile(string $epubDir): ?string
    {
        // First check META-INF/container.xml
        $containerPath = $epubDir.'/META-INF/container.xml';
        if (file_exists($containerPath)) {
            $content = file_get_contents($containerPath);
            $container = $content !== false ? simplexml_load_string($content) : false;
            if ($container !== false) {
                $rootfiles = $container->rootfiles->rootfile;
                foreach ($rootfiles as $rootfile) {
                    $opfPath = $epubDir.'/'.$rootfile['full-path'];
                    if (file_exists($opfPath)) {
                        return $opfPath;
                    }
                }
            }
        }

        // Fallback: search for .opf files
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($epubDir)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && strtolower($file->getExtension()) === 'opf') {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Modify the OPF file with metadata from the Book entity
     */
    private function modifyOpfMetadata(string $opfPath, Book $book): void
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if (!$dom->load($opfPath)) {
            throw new \RuntimeException('Could not load OPF file');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('dc', self::NS_DC);
        $xpath->registerNamespace('opf', self::NS_OPF);

        // Get metadata element
        $metadataNodes = $xpath->query('//opf:metadata');
        if ($metadataNodes === false || $metadataNodes->length === 0) {
            throw new \RuntimeException('Could not find metadata element in OPF');
        }
        $metadata = $metadataNodes->item(0);
        if (!$metadata instanceof \DOMNode) {
            throw new \RuntimeException('Invalid metadata element in OPF');
        }

        // Update title
        $this->updateOrCreateElement($dom, $xpath, $metadata, 'dc:title', $book->getTitle());

        // Update authors
        $this->removeElements($xpath, '//dc:creator');
        foreach ($book->getAuthors() as $author) {
            $creatorNode = $dom->createElementNS(self::NS_DC, 'dc:creator', htmlspecialchars($author));
            $creatorNode->setAttribute('opf:role', 'aut');
            $metadata->appendChild($creatorNode);
        }

        // Update description
        if ($book->getSummary() !== null) {
            $this->updateOrCreateElement($dom, $xpath, $metadata, 'dc:description', $book->getSummary());
        }

        // Update publisher
        if ($book->getPublisher() !== null) {
            $this->updateOrCreateElement($dom, $xpath, $metadata, 'dc:publisher', $book->getPublisher());
        }

        // Update language
        if ($book->getLanguage() !== null) {
            $this->updateOrCreateElement($dom, $xpath, $metadata, 'dc:language', $book->getLanguage());
        }

        // Update subjects/tags
        if ($book->getTags() !== null) {
            $this->removeElements($xpath, '//dc:subject');
            foreach ($book->getTags() as $tag) {
                $subjectNode = $dom->createElementNS(self::NS_DC, 'dc:subject', htmlspecialchars($tag));
                $metadata->appendChild($subjectNode);
            }
        }

        // Update series metadata (using calibre namespace)
        if ($book->getSerie() !== null) {
            $this->updateOrCreateMeta($dom, $xpath, $metadata, 'calibre:series', $book->getSerie());
            if ($book->getSerieIndex() !== null) {
                $this->updateOrCreateMeta($dom, $xpath, $metadata, 'calibre:series_index', (string) $book->getSerieIndex());
            }
        }

        // Add modification timestamp
        $this->updateOrCreateElement($dom, $xpath, $metadata, 'dc:date', date('Y-m-d\TH:i:s\Z'));

        // Save the modified OPF
        $dom->save($opfPath);
    }

    /**
     * Update or create a DC element
     */
    private function updateOrCreateElement(\DOMDocument $dom, \DOMXPath $xpath, \DOMNode $metadata, string $elementName, string $value): void
    {
        $elements = $xpath->query('//'.$elementName);

        if ($elements !== false && $elements->length > 0) {
            $firstElement = $elements->item(0);
            if ($firstElement instanceof \DOMNode) {
                $firstElement->nodeValue = htmlspecialchars($value);
            }

            return;
        }
        $namespace = self::NS_DC;
        $node = $dom->createElementNS($namespace, $elementName, htmlspecialchars($value));
        $metadata->appendChild($node);
    }

    /**
     * Update or create a meta element (for calibre metadata)
     */
    private function updateOrCreateMeta(\DOMDocument $dom, \DOMXPath $xpath, \DOMNode $metadata, string $name, string $content): void
    {
        $metas = $xpath->query("//opf:meta[@name='".$name."']");

        if ($metas !== false && $metas->length > 0) {
            $firstMeta = $metas->item(0);
            if ($firstMeta instanceof \DOMElement) {
                $firstMeta->setAttribute('content', htmlspecialchars($content));
            }
        } else {
            $metaNode = $dom->createElement('meta');
            $metaNode->setAttribute('name', $name);
            $metaNode->setAttribute('content', htmlspecialchars($content));
            $metadata->appendChild($metaNode);
        }
    }

    /**
     * Remove elements matching XPath query
     */
    private function removeElements(\DOMXPath $xpath, string $query): void
    {
        $elements = $xpath->query($query);
        if ($elements === false) {
            return;
        }
        /** @var \DOMNode $element */
        foreach ($elements as $element) {
            if ($element->parentNode instanceof \DOMNode) {
                $element->parentNode->removeChild($element);
            }
        }
    }

    /**
     * Create EPUB file from directory
     */
    private function createEpubFromDirectory(string $sourceDir, string $epubPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($epubPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create EPUB file');
        }

        // Add mimetype first (uncompressed)
        $mimetypePath = $sourceDir.'/mimetype';
        if (file_exists($mimetypePath)) {
            $zip->addFile($mimetypePath, 'mimetype');
            $zip->setCompressionName('mimetype', \ZipArchive::CM_STORE);
        }

        // Add all other files
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                $filePath = $file->getPathname();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);

                // Skip mimetype as we already added it
                if ($relativePath !== 'mimetype') {
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }

        $zip->close();
    }
}
