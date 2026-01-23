<?php

namespace App\Controller\Kobo\Api\V1;

use App\Controller\Kobo\AbstractKoboController;
use App\Kobo\Proxy\KoboStoreProxy;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}/v1', name: 'kobo_')]
class GenericController extends AbstractKoboController
{
    public function __construct(
        protected KoboStoreProxy $koboStoreProxy,
        private readonly LoggerInterface $koboLogger,
    ) {
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/user/loyalty/benefits')]
    public function benefits(Request $request): Response
    {
        if ($this->koboStoreProxy->isEnabled()) {
            return $this->koboStoreProxy->proxyOrRedirect($request);
        }

        return new JsonResponse(['Benefits' => new \stdClass()]);
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/affiliate', methods: ['GET', 'POST'])] // ?PlatformID=00000000-0000-0000-0000-000000000384&SerialNumber=xxxxxxx
    #[Route('/assets', methods: ['GET'])]
    #[Route('/deals', methods: ['GET', 'POST'])]
    #[Route('/products', methods: ['GET', 'POST'])]
    #[Route('/products/books/external/{uuid}', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/products/books/series/{uuid}', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/products/books/{uuid}', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/products/books/{uuid}/', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/products/books/{uuid}/access', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/products/dailydeal', methods: ['GET', 'POST'])]
    #[Route('/products/deals', methods: ['GET', 'POST'])]
    #[Route('/products/featured/', methods: ['GET', 'POST'])]
    #[Route('/products/featured/{uuid}', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/products/{uuid}/prices', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/products/{uuid}/recommendations', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/user/recommendations/feedback', methods: ['GET', 'POST'])]
    #[Route('/products/{uuid}/reviews', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/user/profile')]
    #[Route('/configuration')]
    #[Route('/auth/device')]
    #[Route('/auth/refresh')]
    #[Route('/library/borrow')]
    #[Route('/auth/exchange')]
    #[Route('/library/{uuid}', methods: ['DELETE'])]
    #[Route('/user/recommendations', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/user/wishlist')] // ?PageSize=100&PageIndex=0
    public function proxy(Request $request): Response
    {
        $this->koboLogger->info('Kobo API Proxy request on '.$request->getPathInfo(), ['request' => $request->getContent(), 'headers' => $request->headers->all()]);

        return $this->koboStoreProxy->proxyOrRedirect($request);
    }
}
