<?php

namespace App\Kobo;

use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Exception\BookFileNotFound;
use App\Kobo\ImageProcessor\CoverTransformer;
use App\Kobo\Kepubify\KepubifyConversionFailed;
use App\Kobo\Kepubify\KepubifyMessage;
use App\Kobo\Kepubify\KepubifyMessageAsync;
use App\Kobo\Kepubify\KepubifyMessageInterface;
use App\Kobo\Response\MetadataResponseService;
use App\Service\BookFileSystemManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DownloadHelper
{
    public function __construct(
        private readonly BookFileSystemManagerInterface $fileSystemManager,
        private readonly CoverTransformer $coverTransformer,
        protected UrlGeneratorInterface $urlGenerator,
        protected LoggerInterface $logger,
        protected MessageBusInterface $messageBus,
    ) {
    }

    protected function getBookFilename(Book $book): string
    {
        return $this->fileSystemManager->getBookFilename($book);
    }

    public function getSize(Book $book): int
    {
        return $this->fileSystemManager->getBookSize($book) ?? 0;
    }

    private function getUrlForKoboDevice(Book $book, KoboDevice $koboDevice, string $extension): string
    {
        // Make sure the extension is "kepub.epub" for Kobo devices
        if ($extension === MetadataResponseService::KEPUB_FORMAT) {
            $extension = '.kepub.epub';
        }

        return $this->urlGenerator->generate('kobo_download', [
            'id' => $book->getId(),
            'accessKey' => $koboDevice->getAccessKey(),
            'extension' => strtolower($extension),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function exists(Book $book): bool
    {
        return $this->fileSystemManager->fileExist($book);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function getCoverResponse(Book $book, int $width, int $height, string $extensionWithDot, bool $grayscale = false, bool $asAttachement = true): StreamedResponse
    {
        $coverPath = $this->fileSystemManager->getCoverFilename($book);
        if ($coverPath === null || false === $this->fileSystemManager->coverExist($book)) {
            throw new BookFileNotFound($coverPath);
        }
        $responseExtensionWithDot = $this->coverTransformer->canConvertFile($coverPath) ? $extensionWithDot : '.'.pathinfo($coverPath, PATHINFO_EXTENSION);
        $response = new StreamedResponse(function () use ($coverPath, $width, $height, $grayscale, $responseExtensionWithDot) {
            $this->coverTransformer->streamFile($coverPath, $width, $height, $responseExtensionWithDot, $grayscale);
        }, Response::HTTP_OK);

        match ($responseExtensionWithDot) {
            CoverTransformer::JPG, CoverTransformer::JPEG => $response->headers->set('Content-Type', 'image/jpeg'),
            CoverTransformer::PNG => $response->headers->set('Content-Type', 'image/png'),
            CoverTransformer::GIF => $response->headers->set('Content-Type', 'image/gif'),
            default => $response->headers->set('Content-Type', 'application/octet-stream'),
        };

        $filename = $book->getImageFilename();
        if ($filename === null) {
            return $response;
        }

        $encodedFilename = rawurlencode($filename);
        $simpleName = rawurlencode(sprintf('book-cover--%s-%s', $book->getId(), preg_replace('/[^a-zA-Z0-9\.\-_]/', '_', $filename)));
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition($asAttachement ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE, $encodedFilename, $simpleName));

        return $response;
    }

    /**
     * @throws NotFoundHttpException Book conversion failed
     */
    public function getResponse(Book $book, string $format): Response
    {
        $bookPath = $this->getBookFilename($book);
        if (false === $this->exists($book)) {
            throw new BookFileNotFound($bookPath);
        }

        $message = null;

        if ($format === MetadataResponseService::KEPUB_FORMAT) {
            try {
                $message = $this->runKepubify($book);
            } catch (KepubifyConversionFailed $e) {
                throw new NotFoundHttpException('Book conversion failed', $e);
            }
        }

        $fileToStream = $message->getDestination() ?? $bookPath;
        $fileSize = $message->getSize() ?? filesize($fileToStream);

        $response = (new BinaryFileResponse($fileToStream, Response::HTTP_OK))
            ->deleteFileAfterSend($message?->getDestination() !== null);

        $filename = basename($book->getBookFilename(), $book->getExtension()).strtolower($format);
        $encodedFilename = rawurlencode($filename);
        $simpleName = rawurlencode(sprintf('book-%s-%s', $book->getId(), preg_replace('/[^a-zA-Z0-9\.\-_]/', '_', $filename)));

        $response->headers->set('Content-Type', match (strtoupper($format)) {
            MetadataResponseService::KEPUB_FORMAT, MetadataResponseService::EPUB_FORMAT, MetadataResponseService::EPUB3_FORMAT => 'application/epub+zip',
            default => 'application/octet-stream',
        });
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $encodedFilename, $simpleName));

        if ($fileSize !== false) {
            $response->headers->set('Content-Length', (string) $fileSize);
        }

        return $response;
    }

    public function coverExist(Book $book): bool
    {
        return $this->fileSystemManager->coverExist($book);
    }

    /**
     * @throws KepubifyConversionFailed
     */
    private function runKepubify(Book $book, bool $async = false): KepubifyMessageInterface
    {
        $message = $async ? new KepubifyMessageAsync($book) : new KepubifyMessage($book);
        $this->messageBus->dispatch($message);

        if (!$async && ($message->getDestination() === null || $message->getSize() === null)) {
            throw new KepubifyConversionFailed($book);
        }

        return $message;
    }

    /**
     * @throws KepubifyConversionFailed
     * @throws BookFileNotFound
     */
    public function getDownloadInfo(Book $book, KoboDevice $koboDevice, string $extension): BookDownloadInfo
    {
        $bookPath = $this->getBookFilename($book);
        if (false === $this->exists($book)) {
            throw new BookFileNotFound($bookPath);
        }

        $url = $this->getUrlForKoboDevice($book, $koboDevice, $extension);

        if (strtoupper($extension) !== MetadataResponseService::KEPUB_FORMAT) {
            return new BookDownloadInfo($this->getSize($book), $url);
        }

        // Convert the book to fetch the final size, asynchronously
        $message = $this->runKepubify($bookPath, true);

        $info = new BookDownloadInfo((int) $message->getSize(), $url);
        if ($message->getDestination() !== null) {
            unlink($message->getDestination());
        }

        return $info;
    }

    public function getKoboFileSize(Book $book)
    {
    }
}
