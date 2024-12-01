<?php

namespace App\Controller\Kobo\Api\V1;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\DownloadHelper;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/kobo/{accessKey}/v1/download', name: 'kobo_')]
class DownloadController extends AbstractKoboController
{
    public function __construct(
        protected BookRepository $bookRepository,
        protected DownloadHelper $downloadHelper,
    ) {
    }

    #[Route('/{id}.{extension}', name: 'download', requirements: ['bookId' => '\d+', 'extension' => '[A-Za-z0-9]+'], methods: ['GET'])]
    public function download(KoboDevice $kobo, Book $book, string $extension): Response
    {
        $this->assertCanDownload($kobo, $book);

        return $this->downloadHelper->getResponse($book, $extension);
    }

    private function assertCanDownload(KoboDevice $kobo, Book $book): void
    {
        // TODO Check permissions with is_granted and a dedicated voter ?
        if (!$this->bookRepository->findByIdAndKoboDevice($book->getId() ?? 0, $kobo) instanceof Book) {
            throw new AccessDeniedException('You are not allowed to download this book');
        }
    }
}