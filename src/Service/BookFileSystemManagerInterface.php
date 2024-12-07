<?php

namespace App\Service;

use App\Entity\Book;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface BookFileSystemManagerInterface
{
    public function getBooksDirectory(): string;

    public function getCoverDirectory(): string;

    public function getBookFilename(Book $book): string;

    public function getBookPublicPath(Book $book): string;

    public function getCoverFilename(Book $book): ?string;

    public function getBookSize(Book $book): ?int;

    public function getCoverSize(Book $book): ?int;

    public function fileExist(Book $book): bool;

    public function coverExist(Book $book): bool;

    public function getBookFile(Book $book): \SplFileInfo;

    public function getFileChecksum(\SplFileInfo $file): string;

    public function getFolderName(\SplFileInfo $file, bool $absolute = false): string;

    public function getCalculatedFilePath(Book $book, bool $realpath): string;

    public function extractCover(Book $book): Book;

    public function extractFilesToRead(Book $book): array;

    public function getCalculatedFileName(Book $book): string;

    public function deleteBookFiles(Book $book): void;

    /**
     * @param array<int, UploadedFile> $files
     */
    public function uploadFilesToConsumeDirectory(array $files): void;

    public function renameFiles(Book $book): Book;

    /**
     * @return \Iterator<\SplFileInfo>
     */
    public function getAllBooksFiles(bool $onlyConsumeDirectory = false): \Iterator;

    public function removeEmptySubFolders(?string $path = null): bool;
}
