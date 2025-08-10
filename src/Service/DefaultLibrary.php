<?php

namespace App\Service;

use App\Entity\LibraryFolder;
use App\Repository\LibraryFolderRepository;

class DefaultLibrary
{
    public function __construct(private readonly LibraryFolderRepository $libraryFolderRepository)
    {
    }

    public function folderData(): LibraryFolder
    {
        $libraryFolder = $this->libraryFolderRepository->findOneBy(['defaultLibrary' => true]);
        if (!$libraryFolder instanceof LibraryFolder) {
            throw new \Exception('Default library folder not found');
        }

        return $libraryFolder;
    }
}
