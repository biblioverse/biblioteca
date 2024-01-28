<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Kobo;
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

    #[Route('/v1/download/{bookId}.{extension}', name: 'download')]
    public function download(Kobo $kobo, Book $book): Response
    {
        $this->assertCanDownload($kobo, $book);

        return $this->downloadHelper->getResponse($book);
    }

    private function assertCanDownload(Kobo $kobo, Book $book): void
    {
        // TODO Check permissions with is_granted and a dedicated voter ?
        if (null === $this->bookRepository->findByIdAndKobo($book->getId() ?? 0, $kobo)) {
            throw new AccessDeniedException('You are not allowed to download this book');
        }
    }
}
