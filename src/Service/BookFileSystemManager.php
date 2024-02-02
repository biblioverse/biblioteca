<?php

namespace App\Service;

use App\Entity\Book;
use Archive7z\Archive7z;
use Kiwilan\Ebook\Ebook;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class BookFileSystemManager
{
    public const ALLOWED_FILE_EXTENSIONS = [
        '*.epub', '*.cbr', '*.cbz', '*.pdf', '*.mobi',
    ];

    public const VALID_COVER_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(
        private Security $security,
        private string $publicDir,
        private string $bookFolderNamingFormat,
        private SluggerInterface $slugger,
        private LoggerInterface $logger)
    {
        if ($this->bookFolderNamingFormat == '') {
            throw new \RuntimeException('Could not get filename format');
        }
    }

    public function getBooksDirectory(): string
    {
        return $this->publicDir.'/books/';
    }

    public function getCoverDirectory(): string
    {
        return $this->publicDir.'/covers/';
    }

    /**
     * @return \Iterator<\SplFileInfo>
     */
    public function getAllBooksFiles(bool $onlyConsumeDirectory = false): \Iterator
    {
        try {
            $finder = new Finder();
            $finder->files()->name(self::ALLOWED_FILE_EXTENSIONS)->sort(function (\SplFileInfo $a, \SplFileInfo $b): int {
                return strcmp($a->getRealPath(), $b->getRealPath());
            });
            if ($onlyConsumeDirectory) {
                $finder->in($this->getBooksDirectory().'/consume');
            } else {
                $finder->in($this->getBooksDirectory());
            }
            $iterator = $finder->getIterator();
        } catch (\Exception $e) {
            $iterator = new \ArrayIterator();
        }

        return $iterator;
    }

    public function getBookFilename(Book $book): string
    {
        $paths = [$this->getBooksDirectory(), $book->getBookPath(), $book->getBookFilename()];

        return $this->handlePath($paths);
    }

    public function getCoverFilename(Book $book): ?string
    {
        $paths = [$this->getCoverDirectory(), $book->getImagePath(), $book->getImageFilename()];
        if (in_array(null, $paths, true)) {
            return null;
        }

        return $this->handlePath($paths);
    }

    public function getBookSize(Book $book): ?int
    {
        if (!$this->fileExist($book)) {
            return null;
        }

        $size = filesize($this->getBookFilename($book));

        return $size === false ? null : $size;
    }

    public function getCoverSize(Book $book): ?int
    {
        if (!$this->coverExist($book)) {
            return null;
        }

        $filename = $this->getCoverFilename($book);
        $size = $filename === null ? false : filesize($filename);

        return $size === false ? null : $size;
    }

    public function fileExist(Book $book): bool
    {
        $path = $this->getBookFilename($book);

        return file_exists($path);
    }

    public function coverExist(Book $book): bool
    {
        $cover = $this->getCoverFilename($book);

        return !($cover === null) && file_exists($cover);
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
        if (!is_string($checkSum)) {
            throw new \RuntimeException('Could not calculate file Checksum');
        }
        $checkSum = explode(' ', $checkSum);

        return $checkSum[0];
    }

    public function getFolderName(\SplFileInfo $file, bool $absolute = false): string
    {
        $path = $absolute ? $file->getRealPath() : str_replace($this->getBooksDirectory(), '', $file->getRealPath());

        return str_replace($file->getFilename(), '', $path);
    }

    private function getPlaceHolders(Book $book): array
    {
        $main = current($book->getAuthors());
        if (false === $main) {
            $main = 'unknown';
        }
        $main = $this->slugger->slug($main);
        $author = mb_strtolower($main);
        $title = mb_strtolower($this->slugger->slug($book->getTitle()));
        $serie = null !== $book->getSerie() ? mb_strtolower($this->slugger->slug($book->getSerie())) : null;
        $firstLetter = mb_substr($main, 0, 1);
        $letter = mb_strtolower($firstLetter);

        return [
            '{author}' => $author,
            '{authorFirst}' => $letter,
            '{title}' => $title,
            '{serie}' => $serie,
            '{serieIndex}' => $book->getSerieIndex(),
            '{language}' => $book->getLanguage() ?? 'not-set',
            '{extension}' => $book->getExtension(),
        ];
    }

    private function calculateFilePath(Book $book): string
    {
        $placeholders = $this->getPlaceHolders($book);

        $path = str_replace(array_keys($placeholders), array_values($placeholders), $this->bookFolderNamingFormat).DIRECTORY_SEPARATOR;

        return str_replace('//', '/', $path);
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
        $placeholders = $this->getPlaceHolders($book);

        $filename = str_replace(array_keys($placeholders), array_values($placeholders), $this->bookFolderNamingFormat);
        $filename = str_replace('/', '', $filename);

        return $this->slugger->slug($filename);
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
                true
            );

            $book->setBookPath($this->getCalculatedFilePath($book, false));
            $book->setBookFilename($this->getCalculatedFileName($book));
        }

        if (null !== $book->getImagePath() && $book->getImagePath().'/' !== $this->getCalculatedImagePath($book, false)) {
            $filesystem->mkdir($this->getCalculatedImagePath($book, true));
            $filesystem->rename(
                $this->getCoverDirectory().$book->getImagePath().'/'.$book->getImageFilename(),
                $this->getCalculatedImagePath($book, true).$this->getCalculatedImageName($book),
                true
            );

            $book->setImagePath($this->getCalculatedImagePath($book, false));
            $book->setImageFilename($this->getCalculatedImageName($book));
        }

        return $book;
    }

    public function removeEmptySubFolders(?string $path = null): bool
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
            true
        );

        $this->logger->info('Rename file', ['from' => $file->getRealPath(), 'to' => $this->getCalculatedImagePath($book, true).$this->getCalculatedImageName($book, $checksum)]);

        $book->setImagePath($this->getCalculatedImagePath($book, false));
        $book->setImageFilename($this->getCalculatedImageName($book, $checksum));
        $book->setImageExtension($ext);

        return $book;
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
                    $book = $this->extractCoverFromGeneralArchive($bookFile, $book);
                    break;
                }
                $book = $this->extractCoverFromRarArchive($bookFile, $book);

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

    public function extractFilesToRead(Book $book): array
    {
        $bookFile = $this->getBookFile($book);
        $files = [];
        switch ($book->getExtension()) {
            case 'cbr':
            case 'cbz':
                $return = shell_exec('unrar lb "'.$bookFile->getRealPath().'"');

                if (!is_string($return)) {
                    $files = $this->extractFilesFromGeneralArchive($bookFile, $book);
                    break;
                }
                $files = $this->extractFilesFromRarArchive($bookFile, $book);

                break;
            case 'pdf':
                $files = $this->extractFilesFromPdf($bookFile, $book);
                break;
            default:
                throw new \RuntimeException('Extension not implemented');
        }

        return $files;
    }

    private function extractCoverFromRarArchive(\SplFileInfo $bookFile, Book $book): Book
    {
        $return = shell_exec('unrar lb "'.$bookFile->getRealPath().'"');

        if (!is_string($return)) {
            $this->logger->error('not a string', ['book' => $bookFile->getRealPath(), 'return' => $return]);

            return $book;
        }
        $entries = explode(PHP_EOL, $return);

        sort($entries);

        $entries = array_values(array_filter($entries, static function ($entry) {
            foreach (self::VALID_COVER_EXTENSIONS as $extension) {
                if (str_ends_with(strtolower($entry), '.'.$extension)) {
                    return true;
                }
            }

            return false;
        }));
        if (count($entries) === 0) {
            $this->logger->error('no errors');

            return $book;
        }
        $cover = current($entries);
        $expl = explode('.', $cover);
        $ext = end($expl);

        $expl = explode('.', $cover);
        $coverFile = explode('/', $cover);
        $coverFile = end($coverFile);
        $ext = end($expl);
        $book->setImageExtension($ext);

        $filesystem = new Filesystem();
        $filesystem->mkdir('/tmp/cover');

        shell_exec('unrar e -ep '.escapeshellarg($bookFile->getRealPath()).' '.escapeshellarg($cover).' -op"/tmp/cover" -y');

        $finder = new Finder();
        $finder->in('/tmp/cover')->name('*')->files();

        $file = null;
        foreach ($finder->getIterator() as $item) {
            $filesystem->rename(
                $item->getRealPath(),
                '/tmp/cover/cover.'.$ext,
                true
            );
            $file = new \SplFileInfo('/tmp/cover/cover.'.$ext);
        }

        if ($file === null) {
            return $book;
        }

        $filesystem->mkdir($this->getCalculatedImagePath($book, true));
        try {
            $checksum = $this->getFileChecksum($file);
        } catch (\Exception $e) {
            $this->logger->error('Could not calculate checksum', ['book' => $bookFile->getRealPath(), 'exception' => $e->getMessage()]);
            $checksum = md5(''.time());
        }
        $filesystem->rename(
            $file->getRealPath(),
            $this->getCalculatedImagePath($book, true).$this->getCalculatedImageName($book, $checksum),
            true
        );

        $book->setImagePath($this->getCalculatedImagePath($book, false));
        $book->setImageFilename($this->getCalculatedImageName($book, $checksum));

        return $book;
    }

    private function extractCoverFromGeneralArchive(\SplFileInfo $bookFile, Book $book): Book
    {
        $archive = new Archive7z($bookFile->getRealPath());

        if (!$archive->isValid()) {
            return $book;
        }

        $entries = [];
        foreach ($archive->getEntries() as $entry) {
            foreach (self::VALID_COVER_EXTENSIONS as $extension) {
                if (str_ends_with(strtolower($entry->getPath()), '.'.$extension)) {
                    $entries[] = $entry->getPath();
                }
            }
        }
        sort($entries);

        if (count($entries) === 0) {
            return $book;
        }

        $filesystem = new Filesystem();

        $filesystem->mkdir('/tmp/cover');

        $archive->setOutputDirectory('/tmp/cover')->extractEntry($entries[0]); // extract the archive

        $finder = new Finder();
        $finder->in('/tmp/cover')->name('*')->files();

        $file = null;

        foreach ($finder->getIterator() as $item) {
            $file = $item;
        }

        if ($file === null) {
            return $book;
        }

        $filesystem->mkdir($this->getCalculatedImagePath($book, true));
        $checksum = $this->getFileChecksum($file);
        $filesystem->rename(
            $file->getRealPath(),
            $this->getCalculatedImagePath($book, true).$this->getCalculatedImageName($book, $checksum),
            true
        );

        $book->setImagePath($this->getCalculatedImagePath($book, false));
        $book->setImageFilename($this->getCalculatedImageName($book, $checksum));
        $book->setImageExtension('jpg');

        return $book;
    }

    private function getReaderFolder(): string
    {
        $user = $this->security->getUser();

        return $this->publicDir.'/tmp/reader/'.$user?->getUserIdentifier();
    }

    private function isCurrentBookInReaderFolder(\SplFileInfo $bookFile): bool
    {
        $filesystem = new Filesystem();
        if (!$filesystem->exists($this->getReaderFolder())) {
            $filesystem->mkdir($this->getReaderFolder());

            return false;
        }

        $finder = new Finder();
        $iterator = $finder->in($this->getReaderFolder())->name('book.txt')->files();
        foreach ($iterator->getIterator() as $item) {
            $content = file_get_contents($item->getRealPath());
            if ($bookFile->getRealPath() === $content) {
                return true;
            }
        }

        $filesystem->remove($this->getReaderFolder());
        $filesystem->mkdir($this->getReaderFolder());
        $filesystem->touch($this->getReaderFolder().'/book.txt');
        file_put_contents($this->getReaderFolder().'/book.txt', $bookFile->getRealPath());

        return false;
    }

    private function listReaderFiles(): array
    {
        $finder = new Finder();
        $fileIterator = $finder->in($this->getReaderFolder())->name('*')->files()->getIterator();
        $files = iterator_to_array($fileIterator);
        $return = [];
        foreach ($files as $file) {
            if (!in_array($file->getExtension(), self::VALID_COVER_EXTENSIONS, true)) {
                continue;
            }
            $return[] = str_replace($this->publicDir, '', $file->getRealPath());
        }

        sort($return);

        return $return;
    }

    private function extractFilesFromRarArchive(\SplFileInfo $bookFile, Book $book): array
    {
        if ($this->isCurrentBookInReaderFolder($bookFile)) {
            return $this->listReaderFiles();
        }
        $return = shell_exec('unrar lb "'.$bookFile->getRealPath().'"');

        if (!is_string($return)) {
            $this->logger->error('not a string', ['book' => $bookFile->getRealPath(), 'return' => $return]);

            return [];
        }
        $entries = explode(PHP_EOL, $return);

        sort($entries);

        $entries = array_values(array_filter($entries, static function ($entry) {
            foreach (self::VALID_COVER_EXTENSIONS as $extension) {
                if (str_ends_with(strtolower($entry), '.'.$extension)) {
                    return true;
                }
            }

            return false;
        }));
        if (count($entries) === 0) {
            $this->logger->error('no errors');

            return [];
        }

        $filesystem = new Filesystem();
        $finder = new Finder();

        foreach ($entries as $key => $entry) {
            shell_exec('unrar e -ep '.escapeshellarg($bookFile->getRealPath()).' '.escapeshellarg($entry).' -op"'.$this->getReaderFolder().'" -y');
            $entryFolder = '';
            $entryFileName = $entry;
            if (str_contains($entry, '/')) {
                $entry = explode('/', $entry);
                $entryFolder = '/'.$entry[0];
                $entryFileName = $entry[1];
            }
            foreach ($finder->in($this->getReaderFolder().$entryFolder)->name($entryFileName)->files()->getIterator() as $entryFile) {
                $filesystem->rename(
                    $entryFile->getRealPath(),
                    $this->getReaderFolder().'/'.$this->getFileExtractedName($book, $key, $entryFile),
                    true
                );
            }
        }

        return $this->listReaderFiles();
    }

    public function getFileExtractedName(Book $book, int $key, \SplFileInfo|string $file): string
    {
        $ext = strtolower(is_string($file) ? $file : $file->getExtension());

        return $book->getSlug().'-'.$book->getId().'-'.str_pad(''.$key, 3, '0', STR_PAD_LEFT).'.'.$ext;
    }

    private function extractFilesFromGeneralArchive(\SplFileInfo $bookFile, Book $book): array
    {
        if ($this->isCurrentBookInReaderFolder($bookFile)) {
            return $this->listReaderFiles();
        }
        $archive = new Archive7z($bookFile->getRealPath());

        if (!$archive->isValid()) {
            return [];
        }

        $entries = [];
        foreach ($archive->getEntries() as $entry) {
            foreach (self::VALID_COVER_EXTENSIONS as $extension) {
                if (str_ends_with(strtolower($entry->getPath()), '.'.$extension)) {
                    $entries[] = $entry->getPath();
                }
            }
        }
        sort($entries);

        if (count($entries) === 0) {
            return [];
        }

        $filesystem = new Filesystem();
        $finder = new Finder();

        foreach ($entries as $key => $entry) {
            $archive->setOutputDirectory($this->getReaderFolder())->extractEntry($entry); // extract the archive
            $entryFolder = '';
            $entryFileName = $entry;
            if (str_contains($entry, '/')) {
                $entry = explode('/', $entry);
                $entryFolder = '/'.$entry[0];
                $entryFileName = $entry[1];
            }
            foreach ($finder->in($this->getReaderFolder().$entryFolder)->name($entryFileName)->files()->getIterator() as $entryFile) {
                $filesystem->rename(
                    $entryFile->getRealPath(),
                    $this->getReaderFolder().'/'.$this->getFileExtractedName($book, $key, $entryFile),
                    true
                );
            }
        }

        return $this->listReaderFiles();
    }

    private function extractFilesFromPdf(\SplFileInfo $bookFile, Book $book): array
    {
        if ($this->isCurrentBookInReaderFolder($bookFile)) {
            return $this->listReaderFiles();
        }

        // try {
        $im = new \Imagick($bookFile->getRealPath()); // 0-first page, 1-second page
        $num_page = $im->getNumberImages();
        $im->clear();
        $im->destroy();
        for ($i = 0; $i < $num_page; $i++) {
            $im = new \Imagick($bookFile->getRealPath().'['.$i.']'); // 0-first page, 1-second page

            $im->setImageColorspace(255); // prevent image colors from inverting
            $im->setImageFormat('jpeg');
            $im->thumbnailImage(1000, 1000, true);

            $im->writeImage($this->getReaderFolder().'/'.$this->getFileExtractedName($book, $i, 'jpg'));
            $im->clear();
            $im->destroy();
        }

        /*} catch (\Exception $e) {
            $this->logger->error('Could not extract pdf', ['book' => $bookFile->getRealPath(), 'exception' => $e->getMessage()]);
            return [];
        }*/

        return $this->listReaderFiles();
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return void
     */
    public function uploadFilesToConsumeDirectory(array $files): void
    {
        $destination = $this->getBooksDirectory().'/consume';
        foreach ($files as $file) {
            $file->move($destination, $file->getClientOriginalName());
        }
    }

    /**
     * @param array{0: string, 1: string, 2: string} $paths
     * @return string
     */
    private function handlePath(array $paths): string
    {
        $base = array_shift($paths);
        $paths = array_map(fn ($item) => ltrim($item, '/'), $paths);
        $paths = array_map(fn ($item) => rtrim($item, '/'), $paths);

        $result = sprintf('%s/%s/%s', $base, ...$paths);

        do {
            $result = str_replace('//', '/', $result);
        } while (str_contains($result, '//'));

        return $result;
    }
}
