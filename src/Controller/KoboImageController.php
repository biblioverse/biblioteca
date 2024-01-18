<?php

namespace App\Controller;

use App\Entity\Kobo;
use App\Kobo\Proxy\KoboProxyConfiguration;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Response\SyncResponseFactory;
use App\Repository\KoboRepository;
use App\Repository\ShelfRepository;
use App\Service\KoboSyncTokenExtractor;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}', name: 'kobo')]
#[Security('is_granted("ROLE_KOBO")')]
class KoboImageController extends AbstractController
{
    public function __construct(
        protected KoboRepository $koboRepository,
        protected KoboStoreProxy $koboStoreProxy,
        protected KoboProxyConfiguration $koboProxyConfiguration,
        protected KoboSyncTokenExtractor $koboSyncTokenExtractor,
        protected ShelfRepository $shelfRepository,
        protected LoggerInterface $logger,
        protected SyncResponseFactory $syncResponseFactory)
    {
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/{ImageId}/{width}/{height}/{Quality}/{isGreyscale}/image.jpg', name: 'image')]
    public function image(Request $request, Kobo $kobo): Response
    {
        return $this->koboStoreProxy->proxy($request);
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/{ImageId}/{width}/{height}/false/image.jpg', name: 'image_quality')]
    public function imageQuality(Request $request, Kobo $kobo): Response
    {
        return $this->koboStoreProxy->proxy($request);
    }
}
