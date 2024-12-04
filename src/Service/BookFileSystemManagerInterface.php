<?php

namespace App\Service;

use App\Entity\Book;

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
}
