<?php

namespace App\Twig\Components;

use App\Entity\Book;
use App\Service\BookFileSystemManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent()]
class UploadBookPicture extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use ComponentToolsTrait;

    #[LiveProp()]
    public Book $book;

    #[LiveProp()]
    public bool $isEditing = false;

    public ?string $flashMessage = null;

    #[LiveAction]
    public function activateEditing(): void
    {
        $this->isEditing = true;
    }

    #[LiveAction]
    public function uploadFiles(Request $request, LoggerInterface $logger, BookFileSystemManager $fileSystemManager, EntityManagerInterface $entityManager): void
    {
        /** @var ?UploadedFile $symfonyFile */
        $symfonyFile = $request->files->getIterator()->current();

        if (null === $symfonyFile) {
            $this->flashMessage = 'No file uploaded';

            return;
        }

        if (UPLOAD_ERR_OK === $symfonyFile->getError()) {
            $logger->info('uploading file '.$symfonyFile->getClientOriginalName());

            $book = $fileSystemManager->uploadBookCover($symfonyFile, $this->book);
            $logger->info('save book ', ['path' => $book->getImagePath(), 'filename' => $book->getImageFilename()]);

            $entityManager->persist($book);
            $entityManager->flush();
            $this->flashMessage = 'Successfully uploaded, please reload the page';
            $this->isEditing = false;

            return;
        }
        $logger->info('Error in file ', ['error' => $symfonyFile->getErrorMessage()]);

        $this->flashMessage = $symfonyFile->getErrorMessage();
    }
}
