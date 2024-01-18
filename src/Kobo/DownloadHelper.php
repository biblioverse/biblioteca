<?php

namespace App\Kobo;

use App\Entity\Book;
use App\Entity\Kobo;
use App\Exception\BookFileNotFound;
use App\Service\BookFileSystemManager;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DownloadHelper
{
    public function __construct(private readonly BookFileSystemManager $fileSystemManager, protected UrlGeneratorInterface $urlGenerator)
    {
    }

    protected function getBookPath(Book $book): string
    {
        return $this->fileSystemManager->getBookPath($book);
    }

    public function getSize(Book $book): int
    {
        return $this->fileSystemManager->getBookSize($book) ?? 0;
    }

    public function getUrlForKobo(Book $book, Kobo $kobo): string
    {
        return $this->urlGenerator->generate('app_kobodownload', [
             'id' => $book->getId(),
             'accessKey' => $kobo->getAccessKey(),
             'extension' => $book->getExtension(),
         ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function exists(Book $book): bool
    {
        return $this->fileSystemManager->fileExist($book);
    }

    /**
     * @param Book $book
     * @return StreamedResponse
     * @throws NotFoundHttpException
     */
    public function getResponse(Book $book): StreamedResponse
    {
        $bookPath = $this->getBookPath($book);
        if (false === $this->exists($book)) {
            throw new BookFileNotFound($bookPath);
        }
        $response = new StreamedResponse(function () use ($bookPath) {
            readfile($bookPath);
        }, 200);

        $filename = $book->getBookFilename().'.'.$book->getExtension();
        $encodedFilename = rawurlencode($filename);
        $simpleName = rawurlencode(sprintf('book-%s-%s', $book->getId(), preg_replace('/[^a-zA-Z0-9\.\-_]/', '_', $filename)));

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition',
            sprintf('attachment; filename="%s"; filename*=UTF-8\'\'%s', $simpleName, $encodedFilename));

        return $response;
    }
}
