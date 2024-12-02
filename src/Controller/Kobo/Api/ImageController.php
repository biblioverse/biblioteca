<?php

namespace App\Controller\Kobo\Api;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\Book;
use App\Entity\KoboDevice;
use App\Kobo\DownloadHelper;
use App\Kobo\Proxy\KoboProxyConfiguration;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Response\SyncResponseFactory;
use App\Repository\BookRepository;
use App\Repository\KoboDeviceRepository;
use App\Repository\ShelfRepository;
use App\Service\KoboSyncTokenExtractor;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}', name: 'kobo_')]
class ImageController extends AbstractKoboController
{
    public function __construct(
        protected KoboDeviceRepository $koboRepository,
        protected KoboStoreProxy $koboStoreProxy,
        protected BookRepository $bookRepository,
        protected KoboProxyConfiguration $koboProxyConfiguration,
        protected KoboSyncTokenExtractor $koboSyncTokenExtractor,
        protected ShelfRepository $shelfRepository,
        protected LoggerInterface $logger,
        protected DownloadHelper $downloadHelper,
        protected SyncResponseFactory $syncResponseFactory)
    {
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/{uuid}/{width}/{height}/false/image.jpg', name: 'image_quality', defaults: ['isGreyscale' => false])]
    #[Route('//{uuid}/{width}/{height}/false/image.jpg', name: 'image_quality_bad', defaults: ['isGreyscale' => false])]
    #[Route('/{uuid}/{width}/{height}/{Quality}/{isGreyscale}/image.jpg', name: 'image')]
    #[Route('//{uuid}/{width}/{height}/{Quality}/{isGreyscale}/image.jpg', name: 'image_bad')]
    public function imageQuality(Request $request, KoboDevice $koboDevice, string $uuid, int $width, int $height, string $isGreyscale): Response
    {
        $isGreyscale = in_array($isGreyscale, ['true', 'True', '1'], true);
        $book = $this->bookRepository->findByUuidAndKoboDevice($uuid, $koboDevice);
        if (!$book instanceof Book) {
            if ($this->koboStoreProxy->isEnabled()) {
                return $this->koboStoreProxy->proxy($request);
            }

            return new JsonResponse(['error' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        $asAttachment = str_contains((string) $request->headers->get('User-Agent'), 'Kobo');

        return $this->downloadHelper->getCoverResponse($book, $width, $height, '.jpg', $isGreyscale, $asAttachment);
    }
}
