<?php

namespace App\Controller\Kobo\Api\V1;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\Book;
use App\Kobo\DownloadHelper;
use App\Repository\BookRepository;
use App\Security\Voter\BookVoter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey:koboDevice}/v1/download', name: 'kobo_')]
class DownloadController extends AbstractKoboController
{
    public function __construct(
        protected BookRepository $bookRepository,
        protected DownloadHelper $downloadHelper,
    ) {
    }

    #[Route('/{id:book}.{extension}', name: 'download', requirements: ['bookId' => '\d+', 'extension' => '[A-Za-z0-9.]+'], methods: ['GET'])]
    public function download(Book $book, string $extension): Response
    {
        $this->denyAccessUnlessGranted(BookVoter::DOWNLOAD, $book, 'You are not allowed to download this book');

        return $this->downloadHelper->getResponse($book, $extension);
    }
}
