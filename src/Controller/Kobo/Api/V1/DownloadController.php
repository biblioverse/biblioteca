<?php

namespace App\Controller\Kobo\Api\V1;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\DownloadHelper;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}/v1/download', name: 'kobo_')]
class DownloadController extends AbstractKoboController
{
    public function __construct(
        protected BookRepository $bookRepository,
        protected DownloadHelper $downloadHelper,
    ) {
    }

    #[Route('/{id}.{extension}', name: 'download', requirements: ['bookId' => '\d+', 'extension' => '[A-Za-z0-9]+'], methods: ['GET'])]
    public function download(KoboDevice $koboDevice, Book $book, string $extension): Response
    {
        // $this->assertCanDownload($koboDevice, $book);

        return $this->downloadHelper->getResponse($book, $extension);
    }

    // private function assertCanDownload(KoboDevice $koboDevice, Book $book): void
    // {
    //    // TODO Check permissions with is_granted and a dedicated voter ?
    //    if (!$this->bookRepository->findByIdAndKoboDevice($book->getId() ?? 0, $koboDevice) instanceof Book) {
    //        // FIXME allow if in reading list
    //        throw new AccessDeniedException('You are not allowed to download this book');
    //    }
    // }
}
