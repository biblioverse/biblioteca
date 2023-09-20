<?php

namespace App\Service;

use App\Entity\Book;
use Archive7z\Archive7z;
use Kiwilan\Ebook\Ebook;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class BookFileSystemManager
{
    public const ALLOWED_FILE_EXTENSIONS = [
        '*.epub', '*.cbr', '*.cbz', '*.pdf', '*.mobi',
    ];

    public function __construct(private KernelInterface $appKernel, private SluggerInterface $slugger, private LoggerInterface $logger)
    {
    }

    public function getBooksDirectory(): string
    {
        return $this->appKernel->getProjectDir().'/public/books/';
    }

    public function getCoverDirectory(): string
    {
        return $this->appKernel->getProjectDir().'/public/covers/';
    }

    /**
     * @return \Iterator<\SplFileInfo>
     */
    public function getAllBooksFiles(): \Iterator
    {
        try {
            $finder = new Finder();
            $finder->files()->name(self::ALLOWED_FILE_EXTENSIONS)->in($this->getBooksDirectory());
            $iterator = $finder->getIterator();
        } catch (\Exception $e) {
            $iterator = new \ArrayIterator();
        }

        return $iterator;
    }

    /**
     * @throws \Exception
     */
    public function getBookFile(Book $book): \SplFileInfo
    {
        $finder = new Finder();
        $filename = str_replace(['[', ']'], '*', $book->getBookFilename());

        $finder->files()->name($filename)->in($this->getBooksDirectory().$book->getBookPath());
        $return = iterator_to_array($finder->getIterator());

        if (0 === count($return)) {
            throw new \RuntimeException('Book file not found '.$book->getBookPath().$book->getBookFilename());
        }

        return current($return);
    }

    /**
     * @throws \Exception
     */
    public function getCoverFile(Book $book): ?\SplFileInfo
    {
        if (null === $book->getImageFilename()) {
            return null;
        }

        $finder = new Finder();
        $finder->files()->name($book->getImageFilename())->in($this->getCoverDirectory().$book->getImagePath());
        $return = iterator_to_array($finder->getIterator());
        if (0 === count($return)) {
            throw new \RuntimeException('Cover file not found:'.$this->getCoverDirectory().$book->getImagePath().'/'.$book->getImageFilename());
        }

        return current($return);
    }

    /**
     * Calculates the checksum of a given file.
     *
     * @param \SplFileInfo $file the file for which to calculate the checksum
     *
     * @return string the checksum of the file
     *
     * @throws \RuntimeException if the checksum calculation fails
     */
    public function getFileChecksum(\SplFileInfo $file): string
    {
        $checkSum = shell_exec('sha1sum -b '.escapeshellarg($file->getRealPath()));

        if (null === $checkSum || false === $checkSum) {
            throw new \RuntimeException('Could not calculate file Checksum');
        }

        [$checkSum] = explode(' ', $checkSum);

        return $checkSum;
    }

    public function getFolderName(\SplFileInfo $file, bool $absolute = false): string
    {
        $path = $absolute ? $file->getRealPath() : str_replace($this->getBooksDirectory(), '', $file->getRealPath());

        return str_replace($file->getFilename(), '', $path);
    }

    private function calculateFilePath(Book $book): string
    {
        $main = current($book->getAuthors());
        if (false === $main) {
            $main = 'unknown';
        }
        $main = $this->slugger->slug($main);
        $author = mb_strtolower($main);
        $title = mb_strtolower($this->slugger->slug($book->getTitle()));
        $serie = null !== $book->getSerie() ? mb_strtolower($this->slugger->slug($book->getSerie())) : null;
        $letter = mb_strtolower(substr($main, 0, 1));
        $path = [$letter];

        $path[] = $author;

        if (null !== $serie) {
            $path[] = $serie;
        }

        $path[] = $title;

        $expectedPath = implode(DIRECTORY_SEPARATOR, $path);

        return $expectedPath.DIRECTORY_SEPARATOR;
    }

    public function getCalculatedFilePath(Book $book, bool $realpath): string
    {
        $expectedPath = $this->calculateFilePath($book);

        return ($realpath ? $this->getBooksDirectory() : '').$expectedPath;
    }

    public function getCalculatedImagePath(Book $book, bool $realpath): string
    {
        $expectedFilepath = $this->calculateFilePath($book);

        return ($realpath ? $this->getCoverDirectory() : '').$expectedFilepath;
    }

    private function calculateFileName(Book $book): string
    {
        $expectedFilename = '';
        if (null !== $book->getSerie()) {
            $expectedFilename .= $book->getSerie().' '.$book->getSerieIndex().' - ';
        }
        $expectedFilename .= $this->slugger->slug($book->getTitle());

        return $this->slugger->slug($expectedFilename);
    }

    public function getCalculatedFileName(Book $book): string
    {
        return $this->calculateFileName($book).'.'.$book->getExtension();
    }

    public function getCalculatedImageName(Book $book, string $checksum = ''): string
    {
        return $checksum.$this->calculateFileName($book).'.'.$book->getImageExtension();
    }

    public function renameFiles(Book $book): Book
    {
        $filesystem = new Filesystem();

        if ($book->getBookPath().'/' !== $this->getCalculatedFilePath($book, false)) {
            $filesystem->mkdir($this->getCalculatedFilePath($book, true));
            $filesystem->rename(
                $this->getBooksDirectory().$book->getBookPath().$book->getBookFilename(),
                $this->getCalculatedFilePath($book, true).$this->getCalculatedFileName($book),
                true);

            $book->setBookPath($this->getCalculatedFilePath($book, false));
            $book->setBookFilename($this->getCalculatedFileName($book));
        }

        if (null !== $book->getImagePath() && $book->getImagePath().'/' !== $this->getCalculatedImagePath($book, false)) {
            $filesystem->mkdir($this->getCalculatedImagePath($book, true));
            $filesystem->rename(
                $this->getCoverDirectory().$book->getImagePath().'/'.$book->getImageFilename(),
                $this->getCalculatedImagePath($book, true).$this->getCalculatedImageName($book),
                true);

            $book->setImagePath($this->getCalculatedImagePath($book, false));
            $book->setImageFilename($this->getCalculatedImageName($book));
        }

        return $book;
    }

    public function removeEmptySubFolders(string $path = null): bool
    {
        if (null === $path) {
            $path = $this->getBooksDirectory();
        }
        $empty = true;

        $files = glob($path.DIRECTORY_SEPARATOR.'{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE);
        if (false !== $files && count($files) > 0) {
            foreach ($files as $file) {
                if (is_dir($file)) {
                    if (!$this->removeEmptySubFolders($file)) {
                        $empty = false;
                    }
                } else {
                    $empty = false;
                }
            }
        }
        if ($empty && is_dir($path) && $path !== $this->getBooksDirectory()) {
            rmdir($path);
        }

        return $empty;
    }

    public function uploadBookCover(UploadedFile $file, Book $book): Book
    {
        $filesystem = new Filesystem();

        $coverFileName = explode('/', $file->getClientOriginalName());
        $this->logger->info('Upload Started', ['filename' => $file->getClientOriginalName(), 'book' => $book->getTitle()]);
        $coverFileName = end($coverFileName);
        $ext = explode('.', $coverFileName);
        $ext = end($ext);

        $book->setImageExtension($ext);

        $checksum = (string) md5_file($file->getRealPath());

        $filesystem->mkdir($this->getCalculatedImagePath($book, true));
        $filesystem->rename(
            $file->getRealPath(),
            $this->getCalculatedImagePath($book, true).$this->getCalculatedImageName($book, $checksum),
            true);

        $this->logger->info('Rename file', ['from' => $file->getRealPath(), 'to' => $this->getCalculatedImagePath($book, true).$this->getCalculatedImageName($book, $checksum)]);

        $book->setImagePath($this->getCalculatedImagePath($book, false));
        $book->setImageFilename($this->getCalculatedImageName($book, $checksum));
        $book->setImageExtension($ext);

        return $book;
    }

    public function downloadBookCover(Book $book, string $url): Book
    {
        $filesystem = new Filesystem();

        $fileContents = file_get_contents($url);

        if (false === $fileContents) {
            throw new \RuntimeException('Could not download file');
        }
        $checksum = md5($fileContents);

        $ext = $this->getImageExtension($url);
        if (null === $ext) {
            throw new \RuntimeException('Could not get image extension');
        }
        $book->setImageExtension($ext);

        $filesystem->mkdir($this->getCalculatedImagePath($book, true));

        $filesystem->dumpFile($this->getCalculatedImagePath($book, true).$this->getCalculatedImageName($book, $checksum), $fileContents);

        $book->setImagePath($this->getCalculatedImagePath($book, false));
        $book->setImageFilename($this->getCalculatedImageName($book, $checksum));

        return $book;
    }

    private function getImageExtension(string $url): ?string
    {
        $mimes = [
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_BMP => 'bmp',
            IMAGETYPE_WEBP => 'webp',
        ];
        $image_type = exif_imagetype($url);

        if (false !== $image_type && array_key_exists($image_type, $mimes)) {
            return $mimes[$image_type];
        }

        return null;
    }

    public function deleteBookFiles(Book $book): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->getBooksDirectory().$book->getBookPath().$book->getBookFilename());
        $this->removeEmptySubFolders($this->getBooksDirectory().$book->getBookPath());

        if ($book->getImageFilename() !== null && $book->getImageFilename() !== '') {
            $filesystem->remove($this->getCoverDirectory().$book->getImagePath().$book->getImageFilename());
            $this->removeEmptySubFolders($this->getCoverDirectory().$book->getImagePath());
        }
    }

    public function extractCover(Book $book): Book
    {
        $filesystem = new Filesystem();
        $bookFile = $this->getBookFile($book);
        switch ($book->getExtension()) {
            case 'epub':
                $ebook = Ebook::read($bookFile->getRealPath());
                if ($ebook === null) {
                    break;
                }
                $cover = $ebook->getCover();

                if ($cover === null || $cover->getPath() === null) {
                    break;
                }
                $coverContent = $cover->getContent();

                $coverFileName = explode('/', $cover->getPath());
                $coverFileName = end($coverFileName);
                $ext = explode('.', $coverFileName);
                $book->setImageExtension(end($ext));

                $coverPath = $this->getCalculatedImagePath($book, true);
                $coverFileName = $this->getCalculatedImageName($book);

                $filesystem = new Filesystem();
                $filesystem->mkdir($coverPath);

                $coverFile = file_put_contents($coverPath.$coverFileName, $coverContent);

                if (false !== $coverFile) {
                    $book->setImagePath($this->getCalculatedImagePath($book, false));
                    $book->setImageFilename($coverFileName);
                }
                break;
            case 'cbr':
            case 'cbz':
                $return = shell_exec('unrar lb "'.$bookFile->getRealPath().'"');

                if (!is_string($return)) {
                    $book = $this->extractFromGeneralArchive($bookFile, $book);
                    break;
                }
                $book = $this->extractFromRarArchive($bookFile, $book);

                break;
            case 'pdf':
                $checksum = md5(''.time());

                try {
                    $im = new \Imagick($bookFile->getRealPath().'[0]'); // 0-first page, 1-second page
                } catch (\Exception $e) {
                    $this->logger->error('Could not extract cover', ['book' => $bookFile->getRealPath(), 'exception' => $e->getMessage()]);
                    break;
                }

                $im->setImageColorspace(255); // prevent image colors from inverting
                $im->setImageFormat('jpeg');
                $im->thumbnailImage(300, 460); // width and height
                $book->setImageExtension('jpg');
                $filesystem->mkdir($this->getCalculatedImagePath($book, true));
                $im->writeImage($this->getCalculatedImagePath($book, true).$this->getCalculatedImageName($book, $checksum));
                $im->clear();
                $im->destroy();
                $book->setImagePath($this->getCalculatedImagePath($book, false));
                $book->setImageFilename($this->getCalculatedImageName($book, $checksum));

                break;
            default:
                throw new \RuntimeException('Extension not implemented');
        }

        return $book;
    }

    private function extractFromRarArchive(\SplFileInfo $bookFile, Book $book): Book
    {
        $return = shell_exec('unrar lb "'.$bookFile->getRealPath().'"');

        if (!is_string($return)) {
            $this->logger->error('not a string', ['book' => $bookFile->getRealPath(), 'return' => $return]);

            return $book;
        }
        $entries = explode(PHP_EOL, $return);

        sort($entries);

        $entries = array_values(array_filter($entries, static function ($entry) {
            return str_ends_with($entry, '.jpg') || str_ends_with($entry, '.jpeg') || str_ends_with($entry, '.png');
        }));
        if (count($entries) === 0) {
            $this->logger->error('no errors');

            return $book;
        }
        $cover = current($entries);

        $expl = explode('.', $cover);
        $coverFile = explode('/', $cover);
        $coverFile = end($coverFile);
        $ext = end($expl);
        $book->setImageExtension($ext);

        shell_exec('unrar e -ep "'.$bookFile->getRealPath().'" "'.$cover.'" -op"/tmp/" -y');

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->getCalculatedImagePath($book, true));
        try {
            $checksum = $this->getFileChecksum(new \SplFileInfo('/tmp/'.$coverFile));
        } catch (\Exception $e) {
            $this->logger->error('Could not calculate checksum', ['book' => $bookFile->getRealPath(), 'exception' => $e->getMessage()]);
            $checksum = md5(''.time());
        }
        $filesystem->rename(
            '/tmp/'.$coverFile,
            $this->getCalculatedImagePath($book, true).$this->getCalculatedImageName($book, $checksum),
            true);

        $book->setImagePath($this->getCalculatedImagePath($book, false));
        $book->setImageFilename($this->getCalculatedImageName($book, $checksum));

        return $book;
    }

    private function extractFromGeneralArchive(\SplFileInfo $bookFile, Book $book): Book
    {
        $archive = new Archive7z($bookFile->getRealPath());

        if (!$archive->isValid()) {
            return $book;
        }

        $entries = [];
        foreach ($archive->getEntries() as $entry) {
            if (str_contains($entry->getPath(), '.jpg') || str_contains($entry->getPath(), '.jpeg')) {
                $entries[] = $entry->getPath();
            }
        }
        ksort($entries);

        sort($entries);

        if (count($entries) === 0) {
            return $book;
        }

        $archive->setOutputDirectory('/tmp')->extractEntry($entries[0]); // extract the archive

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->getCalculatedImagePath($book, true));
        $checksum = $this->getFileChecksum(new \SplFileInfo('/tmp/'.$entries[0]));
        $filesystem->rename(
            '/tmp/'.$entries[0],
            $this->getCalculatedImagePath($book, true).$this->getCalculatedImageName($book, $checksum),
            true);

        $book->setImagePath($this->getCalculatedImagePath($book, false));
        $book->setImageFilename($this->getCalculatedImageName($book, $checksum));
        $book->setImageExtension('jpg');

        return $book;
    }
}
