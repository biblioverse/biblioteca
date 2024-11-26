<?php

namespace App\Controller\Kobo;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\DownloadHelper;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Repository\BookRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/kobo/{accessKey}', name: 'app_kobo')]
class KoboDownloadController extends AbstractController
{
    public function __construct(
        protected BookRepository $bookRepository,
        protected DownloadHelper $downloadHelper,
        protected KoboStoreProxy $koboStoreProxy,
        protected LoggerInterface $logger)
    {
    }

    #[Route('/v1/download/{id}.{extension}', name: 'download', requirements: ['bookId' => '\d+', 'extension' => '[A-Za-z0-9]+'], methods: ['GET'])]
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
