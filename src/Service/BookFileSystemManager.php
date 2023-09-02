<?php

namespace App\Service;

use App\Entity\Book;
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

    public const CHUNK = 65536;

    public KernelInterface $appKernel;
    private SluggerInterface $slugger;

    public function __construct(KernelInterface $appKernel, SluggerInterface $slugger)
    {
        $this->appKernel = $appKernel;
        $this->slugger = $slugger;
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
        $finder->files()->name($book->getBookFilename())->in($this->getBooksDirectory().$book->getBookPath());
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
        $author = mb_strtolower($this->slugger->slug($book->getMainAuthor()));
        $title = mb_strtolower($this->slugger->slug($book->getTitle()));
        $serie = null !== $book->getSerie() ? mb_strtolower($this->slugger->slug($book->getSerie())) : null;
        $letter = $author[0];
        $path = [$letter];

        if (null !== $serie) {
            $path[] = $serie;
        }

        $path[] = $author;
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
        $expectedFilename .= $book->getTitle();

        return str_replace('/', '', $expectedFilename);
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
        $files = glob($path.DIRECTORY_SEPARATOR.'*');
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
        if ($empty) {
            rmdir($path);
        }

        return $empty;
    }

    public function uploadBookCover(UploadedFile $file, Book $book): Book
    {
        $filesystem = new Filesystem();

        $coverFileName = explode('/', $file->getClientOriginalName());
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

        $book->setImagePath($this->getCalculatedImagePath($book, false));
        $book->setImageFilename($this->getCalculatedImageName($book, $checksum));
        $book->setImageExtension($ext);

        return $book;
    }
}