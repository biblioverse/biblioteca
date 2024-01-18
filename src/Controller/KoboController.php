<?php

namespace App\Controller;

use App\Kobo\Proxy\KoboStoreProxy;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}', name: 'kobo')]
class KoboController extends AbstractController
{
    public function __construct(
        protected KoboStoreProxy $koboStoreProxy,
    ) {
    }

    #[Route('/', name: 'api_endpoint')]
    public function index(): Response
    {
        return new Response('<html><body>Hello Kobo</body></html>');
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/v1/affiliate', methods: ['GET', 'POST'])] // ?PlatformID=00000000-0000-0000-0000-000000000384&SerialNumber=xxxxxxx
    #[Route('/v1/assets', methods: ['GET'])]
    #[Route('/v1/deals', methods: ['GET', 'POST'])]
    #[Route('/v1/products', methods: ['GET', 'POST'])]
    #[Route('/v1/products/books/external/{uuid}', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/v1/products/books/series/{uuid}', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/v1/products/books/{uuid}', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/v1/products/books/{uuid}/', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/v1/products/dailydeal', methods: ['GET', 'POST'])]
    #[Route('/v1/products/deals', methods: ['GET', 'POST'])]
    #[Route('/v1/products/featured/', methods: ['GET', 'POST'])]
    #[Route('/v1/products/featured/{uuid}', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/v1/products/{uuid}/nextread', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/v1/products/{uuid}/prices', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/v1/products/{uuid}/recommendations', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/v1/products/{uuid}/reviews', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/v1/user/profile')]
    #[Route('/v1/library/{uuid}', methods: ['DELETE'])]
    #[Route('/v1/user/recommendations', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/v1/user/wishlist')] // ?PageSize=100&PageIndex=0
    public function proxy(Request $request): Response
    {
        return $this->koboStoreProxy->proxyOrRedirect($request);
    }
}
