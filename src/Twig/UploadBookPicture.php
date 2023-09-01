<?php

namespace App\Twig;

use App\Entity\Book;
use App\Service\BookFileSystemManager;
use Doctrine\ORM\EntityManagerInterface;
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
    public function uploadFiles(Request $request, BookFileSystemManager $fileSystemManager, EntityManagerInterface $entityManager): void
    {

        /** @var UploadedFile $symfonyFile */
        $symfonyFile = $request->files->getIterator()->current();

        $book = $fileSystemManager->uploadBookCover($symfonyFile, $this->book);

        $entityManager->persist($book);
        $entityManager->flush();
        $this->dispatchBrowserEvent('manager:flush');
        $this->isEditing = false;

        $this->flashMessage = ' book updated';

        $this->dispatchBrowserEvent('manager:flush');

    }
}
