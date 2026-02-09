<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\RemoteBook;
use App\Security\Voter\RelocationVoter;
use Archive7z\Archive7z;
use Kiwilan\Ebook\Ebook;
use Kiwilan\Ebook\EbookCover;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class BookFileSystemManager implements BookFileSystemManagerInterface
{
    public const ALLOWED_FILE_EXTENSIONS = [
        '*.epub', '*.cbr', '*.cbz', '*.pdf', '*.mobi',
    ];

    public const VALID_COVER_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(
        private readonly Security $security,
        private readonly FilesystemOperator $publicStorage,
        private readonly string $publicDir,
        private readonly string $bookFolderNamingFormat,
        private readonly string $bookFileNamingFormat,
        private readonly SluggerInterface $slugger,
        private readonly LoggerInterface $logger,
        private readonly RouterInterface $router,
    ) {
        if ($this->bookFolderNamingFormat === '') {
            throw new \RuntimeException('Could not get filename format');
        }
    }

    protected function getBooksDirectory(): string
    {
        return 'books/';
    }

    protected function getCoverDirectory(): string
    {
        return 'covers/';
    }

    public function getLocalConsumeDirectory(): string
    {
        return $this->publicDir.'/'.$this->getBooksDirectory().'consume/';
    }

    /**
     * @return \SplFileInfo[]
     */
    #[\Override]
    public function getAllConsumeFiles(): array
    {
        try {
            $finder = new Finder();
            $finder->files()->name(self::ALLOWED_FILE_EXTENSIONS)->sort(fn (\SplFileInfo $a, \SplFileInfo $b): int => strcmp($a->getRealPath(), $b->getRealPath()));
            $finder->in($this->getLocalConsumeDirectory());
            $iterator = $finder->getIterator();
        } catch (\Exception) {
            $iterator = new \ArrayIterator();
        }

        return iterator_to_array($iterator);
    }

    #[\Override]
    public function getBookFilename(Book $book): string
    {
        $paths = [$this->getBooksDirectory(), $book->getBookPath(), $book->getBookFilename()];

        return $this->handlePath($paths);
    }

    #[\Override]
    public function getBookPublicPath(Book $book): string
    {
        $path = $this->getBookFilename($book);
        $path = str_replace($this->publicDir, '', $path);
        $encodedBasename = dirname($path).'/'.rawurlencode(basename($path));

        return rtrim($this->router->generate('app_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL), '/').'/'.$encodedBasename;
    }

    #[\Override]
    public function getCoverFilename(Book $book): ?string
    {
        $paths = [$this->getCoverDirectory(), $book->getImagePath(), $book->getImageFilename()];
        if (in_array(null, $paths, true)) {
            return null;
        }

        return $this->handlePath($paths);
    }

    #[\Override]
    public function getBookSize(Book $book): ?int
    {
        try {
            if (!$this->publicStorage->fileExists($this->getBookFilename($book))) {
                return null;
            }

            return $this->publicStorage->fileSize($this->getBookFilename($book));
        } catch (FilesystemException) {
            return null;
        }
    }

    #[\Override]
    public function getCoverSize(Book $book): ?int
    {
        if (!$this->coverExist($book)) {
            return null;
        }

        $filename = $this->getCoverFilename($book);
        $size = $filename === null ? false : $this->publicStorage->fileSize($filename);

        return $size === false ? null : $size;
    }

    #[\Override]
    public function fileExist(Book $book): bool
    {
        return $this->publicStorage->fileExists($this->getBookFilename($book));
    }

    #[\Override]
    public function coverExist(Book $book): bool
    {
        $cover = $this->getCoverFilename($book);

        return $cover !== null && $this->publicStorage->fileExists($cover);
    }

    /**
     * @throws \Exception
     */
    #[\Override]
    public function getBookFile(Book $book): RemoteBook
    {
        $filename = str_replace(['[', ']'], '*', $book->getBookFilename());
        $regex = '/^'.str_replace('\*', '.*', preg_quote($filename, '/')).'$/i';
        foreach ($this->publicStorage->listContents($this->getBooksDirectory().$book->getBookPath(), false) as $attr) {
            if (!$attr->isFile()) {
                continue;
            }

            $basename = basename($attr->path());
            if (preg_match($regex, $basename) === 1) {
                return new RemoteBook(substr($attr->path(), strlen($this->getBooksDirectory())));
            }
        }

        throw new \RuntimeException('Book file not found '.$book->getBookPath().$book->getBookFilename());
    }

    /**
     * @throws \Exception
     */
    public function getCoverFile(Book $book): ?RemoteBook
    {
        if (null === $book->getImageFilename()) {
            return null;
        }

        $cover = $book->getImagePath().$book->getImageFilename();
        if ($this->publicStorage->fileExists($this->getCoverDirectory().$cover)) {
            return new RemoteBook($cover);
        }

        throw new \RuntimeException('Cover file not found:'.$book->getImagePath().'/'.$book->getImageFilename());
    }

    /**
     * Calculates the checksum of a given file.
     *
     * @param RemoteBook|Book|\SplFileInfo $remote the file for which to calculate the checksum
     *
     * @return string the checksum of the file
     *
     * @throws \RuntimeException|FilesystemException if the checksum calculation fails
     */
    #[\Override]
    public function getFileChecksum(RemoteBook|Book|\SplFileInfo $remote): string
    {
        if ($remote instanceof \SplFileInfo) {
            $checkSum = shell_exec('sha1sum -b '.escapeshellarg($remote->getRealPath()));
            if (!is_string($checkSum)) {
                throw new \RuntimeException('Could not calculate file Checksum:'.$remote->getRealPath());
            }
            $checkSum = explode(' ', $checkSum);

            return $checkSum[0];
        }

        if (false === $remote instanceof RemoteBook) {
            $remote = $this->getBookFile($remote);
        }
        if (false === $this->publicStorage->fileExists($this->getBooksDirectory().$remote->path)) {
            throw new \RuntimeException('File not found: '.$remote->path);
        }
        try {
            $stream = $this->publicStorage->readStream($this->getBooksDirectory().$remote->path);
            try {
                $hash = hash_init('sha1');
                hash_update_stream($hash, $stream);

                return hash_final($hash);
            } finally {
                fclose($stream);
            }
        } catch (\Exception $exception) {
            throw new \RuntimeException('Could not calculate file Checksum: '.$remote->path, $exception->getCode(), $exception);
        }
    }

    #[\Override]
    public function getFolderName(RemoteBook $file, bool $absolute = false): string
    {
        $path = str_replace($this->getBooksDirectory(), '', $file->path);

        return dirname($path).'/';
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
            '{author-uc}' => ucwords($author, " \t\r\n\f\v-"),
            '{author-raw}' => current($book->getAuthors()),
            '{authorFirst}' => $letter,
            '{title}' => $title,
            '{title-uc}' => ucwords($title, " \t\r\n\f\v-"),
            '{title-raw}' => $book->getTitle(),
            '{serie}' => $serie,
            '{serie-uc}' => (null !== $serie) ? ucwords($serie, " \t\r\n\f\v-") : null,
            '{serie-raw}' => $book->getSerie(),
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

    #[\Override]
    public function getCalculatedFilePath(Book $book, bool $realpath): string
    {
        $expectedPath = $this->calculateFilePath($book);

        return ($realpath ? $this->getBooksDirectory() : '').$expectedPath;
    }

    public function getCalculatedImagePath(Book $book, bool $prefix): string
    {
        $expectedFilepath = $this->calculateFilePath($book);

        return ($prefix ? $this->getCoverDirectory() : '').$expectedFilepath;
    }

    private function calculateFileName(Book $book): string
    {
        $placeholders = $this->getPlaceHolders($book);

        $filename = str_replace(array_keys($placeholders), array_values($placeholders), $this->bookFileNamingFormat);
        $filename = str_replace('/', '', $filename);

        return $this->slugger->slug($filename);
    }

    #[\Override]
    public function getCalculatedFileName(Book $book): string
    {
        return $this->calculateFileName($book).'.'.$book->getExtension();
    }

    public function getCalculatedImageName(Book $book, string $checksum = ''): string
    {
        return $checksum.$this->calculateFileName($book).'.'.$book->getImageExtension();
    }

    public function downloadToTempFile(RemoteBook|Book $remote): \SplFileInfo
    {
        if (false === $remote instanceof RemoteBook) {
            $remote = $this->getBookFile($remote);
        }
        if (false === $this->publicStorage->fileExists($this->getBooksDirectory().$remote->path)) {
            throw new \RuntimeException(sprintf('File "%s" does not exist', $remote->path));
        }

        $stream = $this->publicStorage->readStream($this->getBooksDirectory().$remote->path);
        $tmpFile = tempnam(sys_get_temp_dir(), 'fly_');
        if ($tmpFile === false) {
            fclose($stream);
            throw new \RuntimeException('Unable to create temporary file');
        }

        $extension = pathinfo($this->getBooksDirectory().$remote->path, PATHINFO_EXTENSION);
        $suffix = $extension !== '' ? '.'.$extension : '';
        $tmpFile .= $suffix;

        $tmpStream = fopen($tmpFile, 'wb');
        if ($tmpStream === false) {
            fclose($stream);
            throw new \RuntimeException('Unable to open temporary file');
        }

        stream_copy_to_stream($stream, $tmpStream);

        fclose($stream);
        fclose($tmpStream);

        return new \SplFileInfo($tmpFile); // caller is responsible for unlink()
    }

    public function uploadCover(\SplFileInfo $file, Book $book): RemoteBook
    {
        $location = $book->getImagePath().$book->getImageFilename();

        return $this->uploadFile($file, $this->getCoverDirectory(), $location);
    }

    public function uploadBook(\SplFileInfo $file, Book $book): RemoteBook
    {
        $location = $book->getBookPath().$book->getBookFilename();

        return $this->uploadFile($file, $this->getBooksDirectory(), $location);
    }

    private function uploadFile(\SplFileInfo $file, string $prefix, string $location): RemoteBook
    {
        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new \RuntimeException('Unable to open file for reading');
        }

        try {
            $this->publicStorage->writeStream($prefix.$location, $stream);
        } finally {
            fclose($stream);
        }

        return new RemoteBook($location);
    }

    public function remove(RemoteBook $remote): void
    {
        if (false === $this->publicStorage->fileExists($remote->path)) {
            return;
        }

        $this->publicStorage->delete($remote->path);
    }

    #[\Override]
    public function renameFiles(Book $book): Book
    {
        if (!$this->security->isGranted(RelocationVoter::RELOCATE, $book)) {
            throw new \RuntimeException('You are not allowed to relocate this book');
        }

        try {
            if ($book->getBookPath().'/' !== $this->getCalculatedFilePath($book, false)) {
                if ($book->getBookPath() === '' || $book->getBookFilename() === '') {
                    throw new \InvalidArgumentException('Book path or filename is empty');
                }
                $this->publicStorage->move(
                    $this->getBooksDirectory().$book->getBookPath().$book->getBookFilename(),
                    $this->getCalculatedFilePath($book, true).$this->getCalculatedFileName($book),
                );

                $book->setBookPath($this->getCalculatedFilePath($book, false));
                $book->setBookFilename($this->getCalculatedFileName($book));
            }

            if (null !== $book->getImagePath() && $book->getImagePath().'/' !== $this->getCalculatedImagePath($book, false)) {
                $this->publicStorage->move(
                    $this->getCoverDirectory().$book->getImagePath().'/'.$book->getImageFilename(),
                    $this->getCoverDirectory().$this->getCalculatedImagePath($book, false).$this->getCalculatedImageName($book),
                );

                $book->setImagePath($this->getCalculatedImagePath($book, false));
                $book->setImageFilename($this->getCalculatedImageName($book));
            }
        } catch (\Exception $e) {
            throw new AccessDeniedException('Relocating this book will overwite another book with the same file name.', 0, $e);
        }

        return $book;
    }

    protected function removeEmptySubFolders(?string $path = null): bool
    {
        // In Flysystem, your filesystem is usually already "rooted" at books/,
        // so root is often '' instead of an absolute OS path.
        $root = rtrim($this->getBooksDirectory(), '/');
        $path = $path === null ? $root : rtrim($path, '/');

        $isRoot = ($path === '' && $root === '') || ($path === $root);

        $empty = true;

        // list immediate children (NOT recursive)
        foreach ($this->publicStorage->listContents($path, false) as $attr) {
            if ($attr->isDir()) {
                // recurse into subdir
                if (!$this->removeEmptySubFolders($attr->path())) {
                    $empty = false;
                }
            } else {
                // any file (including dotfiles) makes directory non-empty
                $empty = false;
            }
        }

        // Delete this directory if it's empty and not the root
        if ($empty && !$isRoot) {
            // deleteDirectory is safe here because we only call it when empty
            // (and on object stores it will typically just ensure prefix is gone)
            $this->publicStorage->deleteDirectory($path);
        }

        return $empty;
    }

    public function uploadBookCover(UploadedFile $file, Book $book): Book
    {
        $coverFileName = explode('/', $file->getClientOriginalName());
        $this->logger->info('Upload Started', ['filename' => $file->getClientOriginalName(), 'book' => $book->getTitle()]);
        $coverFileName = end($coverFileName);
        $ext = explode('.', $coverFileName);
        $ext = end($ext);
        $checksum = $this->getFileChecksum($file->getFileInfo());

        $book->setImageExtension($ext);
        $book->setImagePath($this->getCalculatedImagePath($book, false));
        $book->setImageFilename($this->getCalculatedImageName($book, $checksum));
        $this->uploadCover($file->getFileInfo(), $book);
        $this->logger->info('Rename file', ['from' => $file->getRealPath(), 'to' => $this->getCalculatedImagePath($book, true).$this->getCalculatedImageName($book, $checksum)]);
        unlink($file->getRealPath());

        return $book;
    }

    #[\Override]
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

    #[\Override]
    public function extractCover(Book $book): Book
    {
        $bookFile = $this->downloadToTempFile($book);
        try {
            switch ($book->getExtension()) {
                case 'epub':
                    $ebook = Ebook::read($bookFile->getRealPath());
                    if (!$ebook instanceof Ebook) {
                        break;
                    }
                    $cover = $ebook->getCover();

                    if (!$cover instanceof EbookCover || $cover->getPath() === null) {
                        break;
                    }
                    $coverContent = $cover->getContents();

                    $coverFileName = explode('/', $cover->getPath());
                    $coverFileName = end($coverFileName);
                    $ext = explode('.', $coverFileName);
                    $book->setImageExtension(end($ext));

                    $coverPath = $this->getCalculatedImagePath($book, false);
                    $coverFileName = $this->getCalculatedImageName($book);
                    $book->setImagePath($coverPath);
                    $book->setImageFilename($coverFileName);

                    $tempCover = tempnam(sys_get_temp_dir(), 'fly_');
                    $coverFile = file_put_contents($tempCover, $coverContent);
                    if ($coverFile === false) {
                        unlink($tempCover);
                        throw new \RuntimeException("Unable to write to $tempCover");
                    }

                    $this->uploadCover(new \SplFileInfo($tempCover), $book);
                    break;
                case 'cbr':
                case 'cbz':
                    $return = shell_exec('unrar lb '.escapeshellarg($bookFile->getRealPath()));

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

                    $im->setImageColorspace(\Imagick::COLORSPACE_RGB); // prevent image colors from inverting
                    $im->setImageFormat('jpeg');
                    $im->thumbnailImage(300, 460); // width and height
                    $book->setImageExtension('jpg');
                    $book->setImageFilename($this->getCalculatedImageName($book, $checksum));
                    $book->setImagePath($this->getCalculatedImagePath($book, false));
                    $tempCover = new \SplFileInfo(tempnam(sys_get_temp_dir(), 'fly_'));
                    try {
                        $im->writeImage($tempCover->getRealPath());
                        $im->clear();
                        $im->destroy();
                        $this->uploadCover($tempCover, $book);
                    } finally {
                        if ($tempCover->isFile()) {
                            unlink($tempCover->getRealPath());
                        }
                    }

                    break;
                default:
                    throw new \RuntimeException('Extension not implemented');
            }

            return $book;
        } finally {
            unlink($bookFile->getRealPath());
        }
    }

    #[\Override]
    public function extractFilesToRead(Book $book): array
    {
        $bookFile = $this->downloadToTempFile($book);
        try {
            $files = [];
            switch ($book->getExtension()) {
                case 'cbr':
                case 'cbz':
                    $return = shell_exec('unrar lb '.escapeshellarg($bookFile->getRealPath()));

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
        } finally {
            unlink($bookFile->getRealPath());
        }
    }

    private function extractCoverFromRarArchive(\SplFileInfo $bookFile, Book $book): Book
    {
        $return = shell_exec('unrar lb '.escapeshellarg($bookFile->getRealPath()));

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
        if ($entries === []) {
            $this->logger->error('no errors');

            return $book;
        }
        $cover = current($entries);
        $expl = explode('.', $cover);
        $ext = end($expl);

        $expl = explode('.', $cover);
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

        if (!$file instanceof \SplFileInfo) {
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

        if ($entries === []) {
            return $book;
        }

        $filesystem = new Filesystem();

        $filesystem->remove('/tmp/cover');
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
        $filesystem->rename($file->getRealPath(), '/tmp/cover/cover.jpg', true);

        $finder->in('/tmp/cover')->name('cover.jpg')->files();

        $file = $finder->getIterator()->current();

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
        $return = shell_exec('unrar lb '.escapeshellarg($bookFile->getRealPath()));

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
        if ($entries === []) {
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

        if ($entries === []) {
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

            $im->setImageColorspace(\Imagick::COLORSPACE_RGB); // prevent image colors from inverting
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
     */
    #[\Override]
    public function uploadFilesToConsumeDirectory(array $files): void
    {
        $destination = $this->getBooksDirectory().'consume';

        if (!mkdir($destination, 0777, true) && !is_dir($destination)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $destination));
        }

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            $explode = explode('.', $originalName);
            $ext = end($explode);
            if (!in_array('*.'.$ext, self::ALLOWED_FILE_EXTENSIONS, true)) {
                throw new \InvalidArgumentException('File type not allowed');
            }
            $file->move($destination, $file->getClientOriginalName());
        }
    }

    /**
     * @param array{0: string, 1: string, 2: string} $paths
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

    public function getFileName(RemoteBook $remote): string
    {
        $dir = pathinfo($remote->path, PATHINFO_DIRNAME);
        if ($dir === '.' || $dir === '') {
            $dir = '';
        }
        $dir = rtrim($dir, '/').'/';
        if ($dir === '/') {
            return $remote->path;
        }

        return str_replace($dir, '', $remote->path);
    }

    public function cleanup(): void
    {
        $this->removeEmptySubFolders($this->getCoverDirectory());
        $this->removeEmptySubFolders($this->getBooksDirectory());
    }

    public function needsRelocation(Book $book): bool
    {
        $a = $this->getCalculatedFilePath($book, false).$this->getCalculatedFileName($book);
        $b = $this->getBookFile($book)->path;

        return $a !== $b;
    }
}
