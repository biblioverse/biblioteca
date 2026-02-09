<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\RemoteBook;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface BookFileSystemManagerInterface
{
    public function getBookFilename(Book $book): string;

    public function getBookPublicPath(Book $book): string;

    public function getCoverFilename(Book $book): ?string;

    public function getBookSize(Book $book): ?int;

    public function getCoverSize(Book $book): ?int;

    public function fileExist(Book $book): bool;

    public function coverExist(Book $book): bool;

    public function getBookFile(Book $book): RemoteBook;

    public function getFileChecksum(RemoteBook $file): string;

    public function getFolderName(RemoteBook $file, bool $absolute = false): string;

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
     * @return \SplFileInfo[]
     */
    public function getAllConsumeFiles(): array;

    public function downloadToTempFile(RemoteBook|Book $book): \SplFileInfo;

    public function getFileName(RemoteBook $remote): string;

    public function uploadCover(\SplFileInfo $file, Book $book): RemoteBook;

    public function uploadBook(\SplFileInfo $file, Book $book): RemoteBook;

    public function remove(RemoteBook $remote): void;

    public function getLocalConsumeDirectory(): string;

    public function cleanup(): void;

    public function needsRelocation(Book $book): bool;
}
